<?php
/**
 * Dashboard Open Houses Section Template
 * 
 * Displays and manages agent's open house events with calendar view and scheduling
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get agent data (available from parent template)
$current_agent_id = $current_agent_id ?? get_current_user_id();

// Handle actions
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// Process actions
if ($action && $event_id) {
    switch ($action) {
        case 'cancel':
            if (wp_verify_nonce($_GET['_wpnonce'], 'cancel_open_house_' . $event_id)) {
                hph_cancel_open_house($event_id);
                wp_redirect(add_query_arg(['section' => 'open-houses', 'message' => 'cancelled']));
                exit;
            }
            break;
        case 'delete':
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_open_house_' . $event_id)) {
                wp_delete_post($event_id, true);
                wp_redirect(add_query_arg(['section' => 'open-houses', 'message' => 'deleted']));
                exit;
            }
            break;
    }
}

// Handle form submission for new open house
if (isset($_POST['hph_create_open_house']) && wp_verify_nonce($_POST['hph_open_house_nonce'], 'create_open_house')) {
    $result = hph_create_open_house($current_agent_id, $_POST);
    if ($result) {
        echo '<script>document.addEventListener("DOMContentLoaded", function() { window.HphDashboard.showToast("Open house scheduled successfully!", "success"); });</script>';
    } else {
        echo '<script>document.addEventListener("DOMContentLoaded", function() { window.HphDashboard.showToast("Error scheduling open house. Please try again.", "error"); });</script>';
    }
}

// Display success messages
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
if ($message) {
    $messages = [
        'cancelled' => __('Open house cancelled successfully.', 'happy-place'),
        'deleted' => __('Open house deleted successfully.', 'happy-place'),
    ];
    
    if (isset($messages[$message])) {
        echo '<script>window.addEventListener("load", function() { window.HphDashboard.showToast("' . esc_js($messages[$message]) . '", "success"); });</script>';
    }
}

// Get current date for calendar
$current_date = current_time('Y-m-d');
$view_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : $current_date;
$view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'upcoming';

// Get open houses
$upcoming_events = hph_get_agent_open_houses($current_agent_id, 'upcoming');
$past_events = hph_get_agent_open_houses($current_agent_id, 'past');
$all_events = array_merge($upcoming_events, $past_events);

// Get agent's listings for dropdown
$agent_listings = get_posts([
    'author' => $current_agent_id,
    'post_type' => 'listing',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);
?>

<div class="hph-open-houses-section">
    
    <!-- Section Header -->
    <div class="hph-section-header hph-d-flex hph-justify-between hph-items-center hph-mb-6">
        <div>
            <h2 class="hph-text-2xl hph-font-bold hph-text-gray-900 hph-mb-2">
                <?php esc_html_e('Open Houses', 'happy-place'); ?>
            </h2>
            <p class="hph-text-gray-600">
                <?php 
                printf(
                    /* translators: %d: number of upcoming events */
                    esc_html__('Manage your open house events • %d upcoming', 'happy-place'),
                    count($upcoming_events)
                ); 
                ?>
            </p>
        </div>
        
        <div class="hph-section-actions">
            <button class="hph-btn hph-btn--primary" data-modal="schedule-open-house">
                <i class="fas fa-plus"></i>
                <?php esc_html_e('Schedule Open House', 'happy-place'); ?>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="hph-open-house-stats">
        <div class="hph-stat-card hph-stat-card--compact">
            <div class="hph-stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="hph-stat-content">
                <div class="hph-stat-value"><?php echo esc_html(count($upcoming_events)); ?></div>
                <div class="hph-stat-label"><?php esc_html_e('Upcoming', 'happy-place'); ?></div>
            </div>
        </div>
        
        <div class="hph-stat-card hph-stat-card--compact">
            <div class="hph-stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="hph-stat-content">
                <div class="hph-stat-value"><?php echo esc_html(array_sum(array_column($upcoming_events, 'expected_visitors'))); ?></div>
                <div class="hph-stat-label"><?php esc_html_e('Expected Visitors', 'happy-place'); ?></div>
            </div>
        </div>
        
        <div class="hph-stat-card hph-stat-card--compact">
            <div class="hph-stat-icon">
                <i class="fas fa-history"></i>
            </div>
            <div class="hph-stat-content">
                <div class="hph-stat-value"><?php echo esc_html(count($past_events)); ?></div>
                <div class="hph-stat-label"><?php esc_html_e('Completed', 'happy-place'); ?></div>
            </div>
        </div>
        
        <div class="hph-stat-card hph-stat-card--compact">
            <div class="hph-stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="hph-stat-content">
                <div class="hph-stat-value"><?php echo esc_html(hph_calculate_avg_visitors($past_events)); ?></div>
                <div class="hph-stat-label"><?php esc_html_e('Avg. Visitors', 'happy-place'); ?></div>
            </div>
        </div>
    </div>

    <!-- View Toggle -->
    <div class="hph-view-selector">
        <div class="hph-view-tabs">
            <button class="hph-view-tab <?php echo $view_mode === 'upcoming' ? 'hph-view-tab--active' : ''; ?>" 
                    data-view="upcoming">
                <i class="fas fa-calendar-alt"></i>
                <?php esc_html_e('Upcoming Events', 'happy-place'); ?>
            </button>
            <button class="hph-view-tab <?php echo $view_mode === 'calendar' ? 'hph-view-tab--active' : ''; ?>" 
                    data-view="calendar">
                <i class="fas fa-calendar"></i>
                <?php esc_html_e('Calendar View', 'happy-place'); ?>
            </button>
            <button class="hph-view-tab <?php echo $view_mode === 'past' ? 'hph-view-tab--active' : ''; ?>" 
                    data-view="past">
                <i class="fas fa-history"></i>
                <?php esc_html_e('Past Events', 'happy-place'); ?>
            </button>
        </div>
    </div>

    <!-- Upcoming Events View -->
    <div class="hph-events-view hph-events-view--upcoming <?php echo $view_mode !== 'upcoming' ? 'hph-d-none' : ''; ?>" data-view="upcoming">
        <?php if (!empty($upcoming_events)) : ?>
            <div class="hph-events-grid">
                <?php foreach ($upcoming_events as $event) : 
                    $listing_id = $event['listing_id'];
                    $listing_title = get_the_title($listing_id);
                    $listing_address = get_field('listing_address', $listing_id);
                    $listing_price = get_field('listing_price', $listing_id);
                    $listing_images = get_field('listing_images', $listing_id);
                    $featured_image = !empty($listing_images) ? $listing_images[0] : get_the_post_thumbnail_url($listing_id, 'medium');
                    
                    $event_date = new DateTime($event['start_date'] . ' ' . $event['start_time']);
                    $days_until = $event_date->diff(new DateTime())->days;
                    $is_today = $event_date->format('Y-m-d') === $current_date;
                    $is_tomorrow = $event_date->format('Y-m-d') === date('Y-m-d', strtotime('+1 day'));
                ?>
                    <div class="hph-event-card" data-event-id="<?php echo esc_attr($event['ID']); ?>">
                        
                        <!-- Event Header -->
                        <div class="hph-event-header">
                            <div class="hph-event-date-badge">
                                <div class="hph-event-day"><?php echo esc_html($event_date->format('j')); ?></div>
                                <div class="hph-event-month"><?php echo esc_html($event_date->format('M')); ?></div>
                            </div>
                            
                            <div class="hph-event-status">
                                <?php if ($is_today) : ?>
                                    <span class="hph-status-badge hph-status-badge--urgent"><?php esc_html_e('Today', 'happy-place'); ?></span>
                                <?php elseif ($is_tomorrow) : ?>
                                    <span class="hph-status-badge hph-status-badge--warning"><?php esc_html_e('Tomorrow', 'happy-place'); ?></span>
                                <?php elseif ($days_until <= 7) : ?>
                                    <span class="hph-status-badge hph-status-badge--info"><?php echo esc_html($days_until . ' days'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Property Info -->
                        <div class="hph-event-property">
                            <?php if ($featured_image) : ?>
                                <img src="<?php echo esc_url($featured_image); ?>" 
                                     alt="<?php echo esc_attr($listing_title); ?>" 
                                     class="hph-event-image"
                                     loading="lazy">
                            <?php else : ?>
                                <div class="hph-event-image-placeholder">
                                    <i class="fas fa-home"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="hph-event-property-info">
                                <h3 class="hph-event-property-title">
                                    <a href="<?php echo esc_url(get_permalink($listing_id)); ?>" target="_blank">
                                        <?php echo esc_html($listing_address ?: $listing_title); ?>
                                    </a>
                                </h3>
                                <?php if ($listing_price) : ?>
                                    <div class="hph-event-property-price">
                                        $<?php echo esc_html(number_format($listing_price)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Event Details -->
                        <div class="hph-event-details">
                            <div class="hph-event-time">
                                <i class="fas fa-clock"></i>
                                <span>
                                    <?php 
                                    echo esc_html(
                                        date('g:i A', strtotime($event['start_time'])) . ' - ' . 
                                        date('g:i A', strtotime($event['end_time']))
                                    ); 
                                    ?>
                                </span>
                            </div>
                            
                            <div class="hph-event-visitors">
                                <i class="fas fa-users"></i>
                                <span>
                                    <?php 
                                    printf(
                                        /* translators: %d: expected number of visitors */
                                        esc_html__('%d expected visitors', 'happy-place'),
                                        $event['expected_visitors']
                                    ); 
                                    ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($event['notes'])) : ?>
                                <div class="hph-event-notes">
                                    <i class="fas fa-sticky-note"></i>
                                    <span><?php echo esc_html(wp_trim_words($event['notes'], 10)); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Event Actions -->
                        <div class="hph-event-actions">
                            <button class="hph-btn hph-btn--outline hph-btn--sm" 
                                    data-action="edit" 
                                    data-event-id="<?php echo esc_attr($event['ID']); ?>">
                                <i class="fas fa-edit"></i>
                                <?php esc_html_e('Edit', 'happy-place'); ?>
                            </button>
                            
                            <button class="hph-btn hph-btn--outline hph-btn--sm" 
                                    data-action="share" 
                                    data-event-id="<?php echo esc_attr($event['ID']); ?>">
                                <i class="fas fa-share"></i>
                                <?php esc_html_e('Share', 'happy-place'); ?>
                            </button>
                            
                            <div class="hph-dropdown">
                                <button class="hph-btn hph-btn--secondary hph-btn--sm hph-dropdown-toggle">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="hph-dropdown-menu">
                                    <a href="#" class="hph-dropdown-item" data-action="view-attendees">
                                        <i class="fas fa-users"></i> <?php esc_html_e('View Attendees', 'happy-place'); ?>
                                    </a>
                                    <a href="#" class="hph-dropdown-item" data-action="duplicate">
                                        <i class="fas fa-copy"></i> <?php esc_html_e('Duplicate', 'happy-place'); ?>
                                    </a>
                                    <div class="hph-dropdown-divider"></div>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'cancel', 'event_id' => $event['ID']]), 'cancel_open_house_' . $event['ID'])); ?>" 
                                       class="hph-dropdown-item hph-dropdown-item--warning">
                                        <i class="fas fa-pause"></i> <?php esc_html_e('Cancel', 'happy-place'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'delete', 'event_id' => $event['ID']]), 'delete_open_house_' . $event['ID'])); ?>" 
                                       class="hph-dropdown-item hph-dropdown-item--danger"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this open house?', 'happy-place'); ?>')">
                                        <i class="fas fa-trash"></i> <?php esc_html_e('Delete', 'happy-place'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="hph-empty-state">
                <div class="hph-empty-state-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h3 class="hph-empty-state-title"><?php esc_html_e('No Upcoming Open Houses', 'happy-place'); ?></h3>
                <p class="hph-empty-state-description">
                    <?php esc_html_e('Schedule your first open house to start attracting potential buyers to your listings.', 'happy-place'); ?>
                </p>
                <button class="hph-btn hph-btn--primary" data-modal="schedule-open-house">
                    <i class="fas fa-plus"></i>
                    <?php esc_html_e('Schedule Open House', 'happy-place'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Calendar View -->
    <div class="hph-events-view hph-events-view--calendar <?php echo $view_mode !== 'calendar' ? 'hph-d-none' : ''; ?>" data-view="calendar">
        <div class="hph-calendar-widget">
            <div class="hph-calendar-header">
                <div class="hph-calendar-nav">
                    <button class="hph-calendar-nav-btn" data-action="prev-month">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <h3 class="hph-calendar-month" id="hph-calendar-month">
                        <?php echo esc_html(wp_date('F Y', strtotime($view_date))); ?>
                    </h3>
                    <button class="hph-calendar-nav-btn" data-action="next-month">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <button class="hph-btn hph-btn--outline hph-btn--sm" data-action="today">
                    <?php esc_html_e('Today', 'happy-place'); ?>
                </button>
            </div>
            
            <div class="hph-calendar-grid" id="hph-calendar-grid">
                <!-- Calendar will be populated by JavaScript -->
                <div class="hph-calendar-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p><?php esc_html_e('Loading calendar...', 'happy-place'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Past Events View -->
    <div class="hph-events-view hph-events-view--past <?php echo $view_mode !== 'past' ? 'hph-d-none' : ''; ?>" data-view="past">
        <?php if (!empty($past_events)) : ?>
            <div class="hph-past-events">
                <?php foreach ($past_events as $event) : 
                    $listing_id = $event['listing_id'];
                    $listing_title = get_the_title($listing_id);
                    $listing_address = get_field('listing_address', $listing_id);
                    $event_date = new DateTime($event['start_date'] . ' ' . $event['start_time']);
                ?>
                    <div class="hph-past-event-item">
                        <div class="hph-past-event-date">
                            <div class="hph-past-event-day"><?php echo esc_html($event_date->format('j')); ?></div>
                            <div class="hph-past-event-month"><?php echo esc_html($event_date->format('M')); ?></div>
                            <div class="hph-past-event-year"><?php echo esc_html($event_date->format('Y')); ?></div>
                        </div>
                        
                        <div class="hph-past-event-info">
                            <h4 class="hph-past-event-title">
                                <?php echo esc_html($listing_address ?: $listing_title); ?>
                            </h4>
                            <div class="hph-past-event-details">
                                <span class="hph-past-event-time">
                                    <i class="fas fa-clock"></i>
                                    <?php echo esc_html(date('g:i A', strtotime($event['start_time']))); ?>
                                </span>
                                <span class="hph-past-event-visitors">
                                    <i class="fas fa-users"></i>
                                    <?php echo esc_html($event['actual_visitors'] ?? $event['expected_visitors']); ?> visitors
                                </span>
                            </div>
                        </div>
                        
                        <div class="hph-past-event-actions">
                            <button class="hph-btn hph-btn--outline hph-btn--sm" data-action="view-report">
                                <i class="fas fa-chart-bar"></i>
                                <?php esc_html_e('View Report', 'happy-place'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="hph-empty-state">
                <div class="hph-empty-state-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3 class="hph-empty-state-title"><?php esc_html_e('No Past Events', 'happy-place'); ?></h3>
                <p class="hph-empty-state-description">
                    <?php esc_html_e('Your completed open houses will appear here.', 'happy-place'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Schedule Open House Modal -->
<div class="hph-modal-overlay" id="schedule-open-house-modal">
    <div class="hph-modal hph-modal--md">
        <div class="hph-modal-header">
            <h3 class="hph-modal-title">
                <i class="fas fa-calendar-plus"></i>
                <?php esc_html_e('Schedule Open House', 'happy-place'); ?>
            </h3>
            <button class="hph-modal-close" data-dismiss="modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="" class="hph-open-house-form">
            <?php wp_nonce_field('create_open_house', 'hph_open_house_nonce'); ?>
            
            <div class="hph-modal-body">
                <div class="hph-form-grid">
                    <div class="hph-form-group hph-form-group--col-12">
                        <label for="listing_id" class="hph-form-label hph-form-label--required">
                            <?php esc_html_e('Select Property', 'happy-place'); ?>
                        </label>
                        <select id="listing_id" name="listing_id" class="hph-form-select" required>
                            <option value=""><?php esc_html_e('Choose a listing...', 'happy-place'); ?></option>
                            <?php foreach ($agent_listings as $listing) : 
                                $address = get_field('listing_address', $listing->ID);
                                $price = get_field('listing_price', $listing->ID);
                            ?>
                                <option value="<?php echo esc_attr($listing->ID); ?>">
                                    <?php echo esc_html($address ?: $listing->post_title); ?>
                                    <?php if ($price) : ?>
                                        - $<?php echo esc_html(number_format($price)); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="hph-form-group hph-form-group--col-6">
                        <label for="start_date" class="hph-form-label hph-form-label--required">
                            <?php esc_html_e('Date', 'happy-place'); ?>
                        </label>
                        <input type="date" 
                               id="start_date" 
                               name="start_date" 
                               class="hph-form-input" 
                               min="<?php echo esc_attr($current_date); ?>"
                               required>
                    </div>
                    
                    <div class="hph-form-group hph-form-group--col-3">
                        <label for="start_time" class="hph-form-label hph-form-label--required">
                            <?php esc_html_e('Start Time', 'happy-place'); ?>
                        </label>
                        <input type="time" 
                               id="start_time" 
                               name="start_time" 
                               class="hph-form-input" 
                               value="14:00"
                               required>
                    </div>
                    
                    <div class="hph-form-group hph-form-group--col-3">
                        <label for="end_time" class="hph-form-label hph-form-label--required">
                            <?php esc_html_e('End Time', 'happy-place'); ?>
                        </label>
                        <input type="time" 
                               id="end_time" 
                               name="end_time" 
                               class="hph-form-input" 
                               value="16:00"
                               required>
                    </div>
                    
                    <div class="hph-form-group hph-form-group--col-6">
                        <label for="expected_visitors" class="hph-form-label">
                            <?php esc_html_e('Expected Visitors', 'happy-place'); ?>
                        </label>
                        <input type="number" 
                               id="expected_visitors" 
                               name="expected_visitors" 
                               class="hph-form-input" 
                               min="1" 
                               max="500"
                               value="20">
                    </div>
                    
                    <div class="hph-form-group hph-form-group--col-6">
                        <label for="contact_required" class="hph-form-label">
                            <?php esc_html_e('Registration Required', 'happy-place'); ?>
                        </label>
                        <select id="contact_required" name="contact_required" class="hph-form-select">
                            <option value="0"><?php esc_html_e('No - Open to all', 'happy-place'); ?></option>
                            <option value="1"><?php esc_html_e('Yes - Require sign-up', 'happy-place'); ?></option>
                        </select>
                    </div>
                    
                    <div class="hph-form-group hph-form-group--col-12">
                        <label for="notes" class="hph-form-label">
                            <?php esc_html_e('Special Instructions/Notes', 'happy-place'); ?>
                        </label>
                        <textarea id="notes" 
                                  name="notes" 
                                  class="hph-form-textarea" 
                                  rows="3"
                                  placeholder="<?php esc_attr_e('Any special instructions for visitors, parking info, etc.', 'happy-place'); ?>"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="hph-modal-footer">
                <button type="button" class="hph-btn hph-btn--secondary" data-dismiss="modal">
                    <?php esc_html_e('Cancel', 'happy-place'); ?>
                </button>
                <button type="submit" name="hph_create_open_house" class="hph-btn hph-btn--primary">
                    <i class="fas fa-calendar-plus"></i>
                    <?php esc_html_e('Schedule Open House', 'happy-place'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Open Houses Section Specific Styles */
.hph-open-house-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--hph-spacing-4);
    margin-bottom: var(--hph-spacing-8);
}

.hph-stat-card--compact {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-4);
    padding: var(--hph-spacing-4);
    background: linear-gradient(135deg, var(--hph-color-white), var(--hph-color-gray-25));
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-xl);
    box-shadow: var(--hph-shadow-sm);
    transition: all var(--hph-transition-base);
}

.hph-stat-card--compact:hover {
    transform: translateY(-2px);
    box-shadow: var(--hph-shadow-md);
}

.hph-stat-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--hph-color-primary-100), var(--hph-color-primary-200));
    color: var(--hph-color-primary-600);
    border-radius: var(--hph-radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--hph-font-size-lg);
    flex-shrink: 0;
    box-shadow: var(--hph-shadow-sm);
}

.hph-stat-content {
    flex: 1;
}

.hph-stat-value {
    font-size: var(--hph-font-size-2xl);
    font-weight: var(--hph-font-bold);
    color: var(--hph-color-gray-900);
    line-height: 1.1;
    margin-bottom: var(--hph-spacing-1);
}

.hph-stat-label {
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: var(--hph-font-medium);
}

.hph-view-selector {
    margin-bottom: var(--hph-spacing-6);
}

.hph-view-tabs {
    display: flex;
    background: var(--hph-color-gray-100);
    border-radius: var(--hph-radius-lg);
    padding: var(--hph-spacing-1);
    gap: var(--hph-spacing-1);
}

.hph-view-tab {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-2);
    padding: var(--hph-spacing-3) var(--hph-spacing-4);
    background: transparent;
    border: none;
    border-radius: var(--hph-radius-md);
    color: var(--hph-color-gray-600);
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-medium);
    cursor: pointer;
    transition: all var(--hph-transition-fast);
}

.hph-view-tab:hover {
    color: var(--hph-color-gray-900);
    background: var(--hph-color-white);
}

.hph-view-tab--active {
    background: var(--hph-color-white);
    color: var(--hph-color-primary-600);
    box-shadow: var(--hph-shadow-sm);
}

.hph-events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--hph-spacing-6);
}

.hph-event-card {
    background: var(--hph-color-white);
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-xl);
    overflow: hidden;
    transition: all var(--hph-transition-base);
    box-shadow: var(--hph-shadow-sm);
}

.hph-event-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--hph-shadow-lg);
    border-color: var(--hph-color-primary-200);
}

.hph-event-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--hph-spacing-4) var(--hph-spacing-5);
    background: linear-gradient(135deg, var(--hph-color-gray-25), var(--hph-color-white));
    border-bottom: 1px solid var(--hph-color-gray-100);
}

.hph-event-date-badge {
    text-align: center;
    padding: var(--hph-spacing-2);
    background: linear-gradient(135deg, var(--hph-color-primary-500), var(--hph-color-primary-600));
    color: var(--hph-color-white);
    border-radius: var(--hph-radius-lg);
    min-width: 60px;
    box-shadow: var(--hph-shadow-sm);
}

.hph-event-day {
    font-size: var(--hph-font-size-lg);
    font-weight: var(--hph-font-bold);
    line-height: 1;
}

.hph-event-month {
    font-size: var(--hph-font-size-xs);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    opacity: 0.9;
}

.hph-status-badge {
    padding: var(--hph-spacing-1) var(--hph-spacing-3);
    border-radius: var(--hph-radius-full);
    font-size: var(--hph-font-size-xs);
    font-weight: var(--hph-font-medium);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hph-status-badge--urgent {
    background: var(--hph-color-danger-light);
    color: var(--hph-color-danger-dark);
}

.hph-status-badge--warning {
    background: var(--hph-color-warning-light);
    color: var(--hph-color-warning-dark);
}

.hph-status-badge--info {
    background: var(--hph-color-primary-100);
    color: var(--hph-color-primary-700);
}

.hph-event-property {
    display: flex;
    gap: var(--hph-spacing-4);
    padding: var(--hph-spacing-5);
}

.hph-event-image {
    width: 80px;
    height: 80px;
    border-radius: var(--hph-radius-lg);
    object-fit: cover;
    flex-shrink: 0;
}

.hph-event-image-placeholder {
    width: 80px;
    height: 80px;
    background: var(--hph-color-gray-200);
    border-radius: var(--hph-radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--hph-color-gray-400);
    font-size: var(--hph-font-size-xl);
    flex-shrink: 0;
}

.hph-event-property-info {
    flex: 1;
    min-width: 0;
}

.hph-event-property-title {
    margin: 0 0 var(--hph-spacing-2);
    font-size: var(--hph-font-size-base);
    font-weight: var(--hph-font-semibold);
    line-height: 1.3;
}

.hph-event-property-title a {
    color: var(--hph-color-gray-900);
    text-decoration: none;
    transition: color var(--hph-transition-fast);
}

.hph-event-property-title a:hover {
    color: var(--hph-color-primary-600);
}

.hph-event-property-price {
    font-size: var(--hph-font-size-lg);
    font-weight: var(--hph-font-bold);
    color: var(--hph-color-primary-600);
}

.hph-event-details {
    padding: 0 var(--hph-spacing-5) var(--hph-spacing-4);
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-2);
}

.hph-event-time,
.hph-event-visitors,
.hph-event-notes {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-2);
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-600);
}

.hph-event-time i,
.hph-event-visitors i,
.hph-event-notes i {
    color: var(--hph-color-primary-500);
    width: 16px;
    flex-shrink: 0;
}

.hph-event-actions {
    padding: var(--hph-spacing-4) var(--hph-spacing-5);
    border-top: 1px solid var(--hph-color-gray-100);
    background: var(--hph-color-gray-25);
    display: flex;
    gap: var(--hph-spacing-3);
    align-items: center;
}

.hph-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: var(--hph-color-gray-200);
    border-radius: var(--hph-radius-lg);
    overflow: hidden;
}

.hph-calendar-loading {
    grid-column: 1 / -1;
    padding: var(--hph-spacing-8);
    text-align: center;
    background: var(--hph-color-white);
    color: var(--hph-color-gray-500);
}

.hph-past-events {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-4);
}

.hph-past-event-item {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-4);
    padding: var(--hph-spacing-4);
    background: var(--hph-color-white);
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-lg);
    transition: all var(--hph-transition-base);
}

.hph-past-event-item:hover {
    box-shadow: var(--hph-shadow-md);
    border-color: var(--hph-color-gray-300);
}

.hph-past-event-date {
    text-align: center;
    padding: var(--hph-spacing-3);
    background: var(--hph-color-gray-100);
    border-radius: var(--hph-radius-lg);
    min-width: 70px;
    flex-shrink: 0;
}

.hph-past-event-day {
    font-size: var(--hph-font-size-xl);
    font-weight: var(--hph-font-bold);
    color: var(--hph-color-gray-900);
    line-height: 1;
}

.hph-past-event-month,
.hph-past-event-year {
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hph-past-event-info {
    flex: 1;
    min-width: 0;
}

.hph-past-event-title {
    font-size: var(--hph-font-size-base);
    font-weight: var(--hph-font-semibold);
    color: var(--hph-color-gray-900);
    margin: 0 0 var(--hph-spacing-2);
}

.hph-past-event-details {
    display: flex;
    gap: var(--hph-spacing-4);
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-600);
}

.hph-past-event-time,
.hph-past-event-visitors {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-1);
}

.hph-past-event-time i,
.hph-past-event-visitors i {
    color: var(--hph-color-primary-500);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hph-open-house-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: var(--hph-spacing-3);
    }
    
    .hph-stat-card--compact {
        flex-direction: column;
        text-align: center;
        gap: var(--hph-spacing-2);
    }
    
    .hph-view-tabs {
        flex-direction: column;
        gap: var(--hph-spacing-2);
    }
    
    .hph-events-grid {
        grid-template-columns: 1fr;
        gap: var(--hph-spacing-4);
    }
    
    .hph-event-property {
        flex-direction: column;
        text-align: center;
    }
    
    .hph-event-actions {
        flex-direction: column;
        gap: var(--hph-spacing-2);
    }
    
    .hph-past-event-item {
        flex-direction: column;
        text-align: center;
        gap: var(--hph-spacing-3);
    }
}

@media (max-width: 480px) {
    .hph-open-house-stats {
        grid-template-columns: 1fr;
    }
    
    .hph-event-header {
        flex-direction: column;
        gap: var(--hph-spacing-3);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View switching
    const viewTabs = document.querySelectorAll('.hph-view-tab');
    const viewContainers = document.querySelectorAll('.hph-events-view');
    
    viewTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetView = this.dataset.view;
            
            // Update active tab
            viewTabs.forEach(t => t.classList.remove('hph-view-tab--active'));
            this.classList.add('hph-view-tab--active');
            
            // Show/hide views
            viewContainers.forEach(container => {
                if (container.dataset.view === targetView) {
                    container.classList.remove('hph-d-none');
                } else {
                    container.classList.add('hph-d-none');
                }
            });
            
            // Initialize calendar if switching to calendar view
            if (targetView === 'calendar') {
                initCalendar();
            }
        });
    });
    
    // Modal functionality
    const scheduleModal = document.getElementById('schedule-open-house-modal');
    const modalTriggers = document.querySelectorAll('[data-modal="schedule-open-house"]');
    const modalCloses = scheduleModal.querySelectorAll('[data-dismiss="modal"]');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            scheduleModal.classList.add('hph-modal-overlay--active');
            document.body.classList.add('hph-modal-open');
        });
    });
    
    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            scheduleModal.classList.remove('hph-modal-overlay--active');
            document.body.classList.remove('hph-modal-open');
        });
    });
    
    // Close modal on overlay click
    scheduleModal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('hph-modal-overlay--active');
            document.body.classList.remove('hph-modal-open');
        }
    });
    
    // Form validation
    const form = scheduleModal.querySelector('.hph-open-house-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const startTime = form.querySelector('#start_time').value;
            const endTime = form.querySelector('#end_time').value;
            
            if (startTime && endTime && startTime >= endTime) {
                e.preventDefault();
                if (window.HphDashboard && window.HphDashboard.showToast) {
                    window.HphDashboard.showToast('End time must be after start time.', 'error');
                }
            }
        });
    }
    
    // Event actions
    document.addEventListener('click', function(e) {
        const action = e.target.closest('[data-action]');
        if (action) {
            const actionType = action.dataset.action;
            const eventId = action.dataset.eventId;
            
            switch (actionType) {
                case 'edit':
                    // Open edit modal
                    console.log('Edit event:', eventId);
                    break;
                case 'share':
                    // Open share modal
                    if (navigator.share) {
                        navigator.share({
                            title: 'Open House Event',
                            text: 'Join us for an open house!',
                            url: window.location.href
                        });
                    } else {
                        // Fallback sharing
                        if (window.HphDashboard && window.HphDashboard.showToast) {
                            window.HphDashboard.showToast('Share feature coming soon!', 'info');
                        }
                    }
                    break;
                case 'view-attendees':
                case 'duplicate':
                case 'view-report':
                    // Placeholder actions
                    if (window.HphDashboard && window.HphDashboard.showToast) {
                        window.HphDashboard.showToast('Feature coming soon!', 'info');
                    }
                    break;
            }
        }
    });
    
    // Calendar initialization
    function initCalendar() {
        const calendarGrid = document.getElementById('hph-calendar-grid');
        if (!calendarGrid || calendarGrid.querySelector('.hph-calendar-day')) {
            return; // Already initialized
        }
        
        // This would be replaced with your actual calendar implementation
        calendarGrid.innerHTML = '<div class="hph-calendar-placeholder">Calendar implementation goes here</div>';
    }
});
</script>

<?php
/**
 * Helper functions for open houses
 */

function hph_get_agent_open_houses($agent_id, $type = 'all') {
    // This is a placeholder - implement your actual data retrieval
    $all_events = [
        [
            'ID' => 1,
            'listing_id' => 123,
            'start_date' => date('Y-m-d', strtotime('+3 days')),
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'expected_visitors' => 25,
            'notes' => 'Parking available on Main Street'
        ],
        [
            'ID' => 2,
            'listing_id' => 124,
            'start_date' => date('Y-m-d', strtotime('+7 days')),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'expected_visitors' => 30,
            'notes' => ''
        ]
    ];
    
    $current_date = current_time('Y-m-d');
    
    switch ($type) {
        case 'upcoming':
            return array_filter($all_events, function($event) use ($current_date) {
                return $event['start_date'] >= $current_date;
            });
        case 'past':
            return array_filter($all_events, function($event) use ($current_date) {
                return $event['start_date'] < $current_date;
            });
        default:
            return $all_events;
    }
}

function hph_create_open_house($agent_id, $data) {
    // Implement open house creation logic
    return true;
}

function hph_cancel_open_house($event_id) {
    // Implement cancellation logic
    return update_post_meta($event_id, 'event_status', 'cancelled');
}

function hph_calculate_avg_visitors($past_events) {
    if (empty($past_events)) {
        return 0;
    }
    
    $total_visitors = array_sum(array_column($past_events, 'actual_visitors'));
    return round($total_visitors / count($past_events));
}
?>