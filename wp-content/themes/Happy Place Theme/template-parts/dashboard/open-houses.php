<?php
/**
 * Dashboard Open Houses Section Template
 * 
 * Manage and schedule open house events
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$current_agent_id = $current_user->ID;

// Get current action
$current_action = sanitize_text_field($_GET['action'] ?? '');
$open_house_id = intval($_GET['open_house_id'] ?? 0);

// Handle form actions
if (in_array($current_action, ['new', 'edit'])) {
    // Load the open house form (we'll create this next if needed)
    ?>
    <div class="hph-dashboard-form-container">
        <div class="hph-section-header">
            <h2 class="hph-section-title">
                <i class="fas fa-calendar-plus"></i>
                <?php echo $current_action === 'edit' ? __('Edit Open House', 'happy-place') : __('Schedule Open House', 'happy-place'); ?>
            </h2>
            <p class="hph-section-description">
                <?php echo $current_action === 'edit' 
                    ? __('Update your open house details and information.', 'happy-place')
                    : __('Schedule a new open house event for one of your listings.', 'happy-place'); ?>
            </p>
        </div>
        
        <form id="hph-open-house-form" class="hph-dashboard-form">
            <?php wp_nonce_field('hph_nonce', 'nonce'); ?>
            <input type="hidden" name="action" value="save_open_house">
            <?php if ($open_house_id) : ?>
                <input type="hidden" name="open_house_id" value="<?php echo $open_house_id; ?>">
            <?php endif; ?>
            
            <!-- Listing Selection -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-home"></i>
                    <?php _e('Property Information', 'happy-place'); ?>
                </h3>
                
                <div class="hph-form-group">
                    <label for="listing_id" class="hph-form-label">
                        <?php _e('Select Listing', 'happy-place'); ?> *
                    </label>
                    <select id="listing_id" name="listing_id" class="hph-form-select" required>
                        <option value=""><?php _e('Choose a listing...', 'happy-place'); ?></option>
                        <?php
                        $listings = get_posts([
                            'author' => $current_agent_id,
                            'post_type' => 'listing',
                            'post_status' => 'publish',
                            'numberposts' => -1
                        ]);
                        foreach ($listings as $listing) :
                            $address = get_field('property_address', $listing->ID);
                        ?>
                            <option value="<?php echo $listing->ID; ?>">
                                <?php echo esc_html($listing->post_title); ?>
                                <?php if ($address) : ?>
                                    - <?php echo esc_html($address); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Event Details -->
            <div class="hph-form-section">
                <h3 class="hph-form-section-title">
                    <i class="fas fa-calendar-alt"></i>
                    <?php _e('Event Details', 'happy-place'); ?>
                </h3>
                
                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="event_date" class="hph-form-label">
                            <?php _e('Date', 'happy-place'); ?> *
                        </label>
                        <input type="date" 
                               id="event_date" 
                               name="event_date" 
                               class="hph-form-input" 
                               required>
                    </div>
                    
                    <div class="hph-form-group">
                        <label for="start_time" class="hph-form-label">
                            <?php _e('Start Time', 'happy-place'); ?> *
                        </label>
                        <input type="time" 
                               id="start_time" 
                               name="start_time" 
                               class="hph-form-input" 
                               required>
                    </div>
                    
                    <div class="hph-form-group">
                        <label for="end_time" class="hph-form-label">
                            <?php _e('End Time', 'happy-place'); ?> *
                        </label>
                        <input type="time" 
                               id="end_time" 
                               name="end_time" 
                               class="hph-form-input" 
                               required>
                    </div>
                </div>
                
                <div class="hph-form-group">
                    <label for="event_description" class="hph-form-label">
                        <?php _e('Description', 'happy-place'); ?>
                    </label>
                    <textarea id="event_description" 
                              name="event_description" 
                              class="hph-form-textarea" 
                              rows="4"
                              placeholder="<?php esc_attr_e('Additional details about the open house...', 'happy-place'); ?>"></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="hph-form-actions">
                <a href="<?php echo esc_url(add_query_arg('section', 'open-houses')); ?>" 
                   class="hph-btn hph-btn--secondary">
                    <i class="fas fa-arrow-left"></i>
                    <?php _e('Back to Open Houses', 'happy-place'); ?>
                </a>
                
                <button type="submit" class="hph-btn hph-btn--primary">
                    <i class="fas fa-save"></i>
                    <?php echo $current_action === 'edit' ? __('Update Open House', 'happy-place') : __('Schedule Open House', 'happy-place'); ?>
                </button>
            </div>
        </form>
    </div>
    <?php
    return;
}

// Default open houses view
// Get open houses (using mock data for now)
$upcoming_events = [
    [
        'id' => 1,
        'listing_title' => 'Beautiful Downtown Condo',
        'address' => '123 Main St, City, ST 12345',
        'date' => '2024-01-15',
        'start_time' => '14:00',
        'end_time' => '16:00',
        'status' => 'scheduled',
        'attendees' => 8
    ],
    [
        'id' => 2,
        'listing_title' => 'Modern Family Home',
        'address' => '456 Oak Ave, City, ST 12345',
        'date' => '2024-01-20',
        'start_time' => '13:00',
        'end_time' => '15:00',
        'status' => 'scheduled',
        'attendees' => 12
    ]
];

$past_events = [
    [
        'id' => 3,
        'listing_title' => 'Luxury Waterfront Property',
        'address' => '789 Lake Dr, City, ST 12345',
        'date' => '2024-01-05',
        'start_time' => '15:00',
        'end_time' => '17:00',
        'status' => 'completed',
        'attendees' => 25
    ]
];
?>

<div class="hph-dashboard-open-houses">
    
    <!-- Open Houses Header -->
    <div class="hph-open-houses-header">
        <div class="hph-open-houses-title-group">
            <h2><?php esc_html_e('Open Houses', 'happy-place'); ?></h2>
            <p class="hph-open-houses-description">
                <?php esc_html_e('Schedule and manage your open house events to attract potential buyers.', 'happy-place'); ?>
            </p>
        </div>
        <div class="hph-open-houses-actions">
            <a href="<?php echo esc_url(add_query_arg(['section' => 'open-houses', 'action' => 'new'])); ?>" 
               class="hph-btn hph-btn--primary">
                <i class="fas fa-plus"></i>
                <?php esc_html_e('Schedule Open House', 'happy-place'); ?>
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="hph-open-houses-stats">
        <div class="hph-stat-item">
            <div class="hph-stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="hph-stat-content">
                <h3><?php echo count($upcoming_events); ?></h3>
                <p><?php esc_html_e('Upcoming Events', 'happy-place'); ?></p>
            </div>
        </div>
        <div class="hph-stat-item">
            <div class="hph-stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="hph-stat-content">
                <h3><?php echo array_sum(array_column($upcoming_events, 'attendees')); ?></h3>
                <p><?php esc_html_e('Expected Attendees', 'happy-place'); ?></p>
            </div>
        </div>
        <div class="hph-stat-item">
            <div class="hph-stat-icon">
                <i class="fas fa-history"></i>
            </div>
            <div class="hph-stat-content">
                <h3><?php echo count($past_events); ?></h3>
                <p><?php esc_html_e('Completed Events', 'happy-place'); ?></p>
            </div>
        </div>
    </div>

    <!-- Open Houses Content -->
    <div class="hph-open-houses-content">
        
        <!-- View Toggle -->
        <div class="hph-view-toggle-container">
            <div class="hph-view-toggle">
                <button type="button" class="hph-view-tab hph-view-tab--active" data-view="upcoming">
                    <i class="fas fa-calendar-alt"></i>
                    <?php esc_html_e('Upcoming', 'happy-place'); ?>
                </button>
                <button type="button" class="hph-view-tab" data-view="past">
                    <i class="fas fa-history"></i>
                    <?php esc_html_e('Past Events', 'happy-place'); ?>
                </button>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="hph-events-section" id="upcoming-events">
            <?php if (!empty($upcoming_events)) : ?>
                <div class="hph-events-grid">
                    <?php foreach ($upcoming_events as $event) : ?>
                        <div class="hph-event-card">
                            <div class="hph-event-card-header">
                                <div class="hph-event-date">
                                    <div class="hph-event-day">
                                        <?php echo date('j', strtotime($event['date'])); ?>
                                    </div>
                                    <div class="hph-event-month">
                                        <?php echo date('M', strtotime($event['date'])); ?>
                                    </div>
                                </div>
                                <div class="hph-event-status hph-event-status--<?php echo esc_attr($event['status']); ?>">
                                    <?php echo esc_html(ucfirst($event['status'])); ?>
                                </div>
                            </div>
                            
                            <div class="hph-event-card-content">
                                <h3 class="hph-event-title"><?php echo esc_html($event['listing_title']); ?></h3>
                                <p class="hph-event-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo esc_html($event['address']); ?>
                                </p>
                                
                                <div class="hph-event-time">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('g:i A', strtotime($event['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($event['end_time'])); ?>
                                </div>
                                
                                <div class="hph-event-attendees">
                                    <i class="fas fa-users"></i>
                                    <?php printf(__('%d expected attendees', 'happy-place'), $event['attendees']); ?>
                                </div>
                            </div>
                            
                            <div class="hph-event-card-actions">
                                <a href="<?php echo esc_url(add_query_arg(['section' => 'open-houses', 'action' => 'edit', 'open_house_id' => $event['id']])); ?>" 
                                   class="hph-btn hph-btn--sm hph-btn--outline">
                                    <i class="fas fa-edit"></i>
                                    <?php esc_html_e('Edit', 'happy-place'); ?>
                                </a>
                                
                                <button type="button" 
                                        class="hph-btn hph-btn--sm hph-btn--primary"
                                        onclick="sendReminders(<?php echo $event['id']; ?>)">
                                    <i class="fas fa-envelope"></i>
                                    <?php esc_html_e('Send Reminders', 'happy-place'); ?>
                                </button>
                                
                                <div class="hph-event-actions-dropdown">
                                    <button type="button" class="hph-btn hph-btn--sm hph-btn--outline hph-dropdown-toggle">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <div class="hph-dropdown-menu">
                                        <button type="button" class="hph-dropdown-item" onclick="viewAttendees(<?php echo $event['id']; ?>)">
                                            <i class="fas fa-users"></i>
                                            <?php esc_html_e('View Attendees', 'happy-place'); ?>
                                        </button>
                                        <button type="button" class="hph-dropdown-item" onclick="duplicateEvent(<?php echo $event['id']; ?>)">
                                            <i class="fas fa-copy"></i>
                                            <?php esc_html_e('Duplicate', 'happy-place'); ?>
                                        </button>
                                        <button type="button" class="hph-dropdown-item hph-dropdown-item--danger" onclick="cancelEvent(<?php echo $event['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                            <?php esc_html_e('Cancel Event', 'happy-place'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="hph-empty-state">
                    <div class="hph-empty-state-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="hph-empty-state-title"><?php esc_html_e('No upcoming open houses', 'happy-place'); ?></h3>
                    <p class="hph-empty-state-description">
                        <?php esc_html_e('Schedule your first open house to start attracting potential buyers.', 'happy-place'); ?>
                    </p>
                    <a href="<?php echo esc_url(add_query_arg(['section' => 'open-houses', 'action' => 'new'])); ?>" 
                       class="hph-btn hph-btn--primary">
                        <i class="fas fa-plus"></i>
                        <?php esc_html_e('Schedule Open House', 'happy-place'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Events -->
        <div class="hph-events-section" id="past-events" style="display: none;">
            <?php if (!empty($past_events)) : ?>
                <div class="hph-events-grid">
                    <?php foreach ($past_events as $event) : ?>
                        <div class="hph-event-card hph-event-card--past">
                            <div class="hph-event-card-header">
                                <div class="hph-event-date">
                                    <div class="hph-event-day">
                                        <?php echo date('j', strtotime($event['date'])); ?>
                                    </div>
                                    <div class="hph-event-month">
                                        <?php echo date('M', strtotime($event['date'])); ?>
                                    </div>
                                </div>
                                <div class="hph-event-status hph-event-status--<?php echo esc_attr($event['status']); ?>">
                                    <?php echo esc_html(ucfirst($event['status'])); ?>
                                </div>
                            </div>
                            
                            <div class="hph-event-card-content">
                                <h3 class="hph-event-title"><?php echo esc_html($event['listing_title']); ?></h3>
                                <p class="hph-event-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo esc_html($event['address']); ?>
                                </p>
                                
                                <div class="hph-event-time">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('g:i A', strtotime($event['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($event['end_time'])); ?>
                                </div>
                                
                                <div class="hph-event-attendees">
                                    <i class="fas fa-users"></i>
                                    <?php printf(__('%d attendees', 'happy-place'), $event['attendees']); ?>
                                </div>
                            </div>
                            
                            <div class="hph-event-card-actions">
                                <button type="button" 
                                        class="hph-btn hph-btn--sm hph-btn--outline"
                                        onclick="viewEventReport(<?php echo $event['id']; ?>)">
                                    <i class="fas fa-chart-bar"></i>
                                    <?php esc_html_e('View Report', 'happy-place'); ?>
                                </button>
                                
                                <button type="button" 
                                        class="hph-btn hph-btn--sm hph-btn--primary"
                                        onclick="duplicateEvent(<?php echo $event['id']; ?>)">
                                    <i class="fas fa-copy"></i>
                                    <?php esc_html_e('Duplicate', 'happy-place'); ?>
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
                    <h3 class="hph-empty-state-title"><?php esc_html_e('No past events', 'happy-place'); ?></h3>
                    <p class="hph-empty-state-description">
                        <?php esc_html_e('Your completed open houses will appear here.', 'happy-place'); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Open Houses section JavaScript
jQuery(document).ready(function($) {
    // View toggle functionality
    $('.hph-view-tab').on('click', function() {
        const view = $(this).data('view');
        
        // Update active tab
        $('.hph-view-tab').removeClass('hph-view-tab--active');
        $(this).addClass('hph-view-tab--active');
        
        // Show/hide sections
        $('.hph-events-section').hide();
        $('#' + view + '-events').show();
    });
    
    // Dropdown menus
    $('.hph-dropdown-toggle').on('click', function(e) {
        e.stopPropagation();
        $('.hph-dropdown-menu').not($(this).siblings('.hph-dropdown-menu')).removeClass('show');
        $(this).siblings('.hph-dropdown-menu').toggleClass('show');
    });
    
    // Close dropdowns on outside click
    $(document).on('click', function() {
        $('.hph-dropdown-menu').removeClass('show');
    });
    
    // Form handling
    $('#hph-open-house-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show flyer generation modal after successful open house scheduling
                    if (typeof HphDashboard !== 'undefined' && HphDashboard.showFlyerGenerationModal) {
                        setTimeout(() => {
                            HphDashboard.currentFlyerContext = 'open_house';
                            HphDashboard.showFlyerGenerationModal();
                        }, 1000);
                        
                        // Redirect after user interacts with flyer modal
                        setTimeout(() => {
                            window.location.href = '<?php echo esc_url(add_query_arg("section", "open-houses")); ?>';
                        }, 5000);
                    } else {
                        // Fallback to immediate redirect if dashboard functions not available
                        window.location.href = '<?php echo esc_url(add_query_arg("section", "open-houses")); ?>';
                    }
                } else {
                    alert(response.data.message || 'Error saving open house.');
                }
            },
            error: function() {
                alert('An error occurred while saving.');
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
});

function sendReminders(eventId) {
    if (!confirm('<?php esc_html_e('Send reminder emails to all registered attendees?', 'happy-place'); ?>')) {
        return;
    }
    
    // AJAX call to send reminders
    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'hph_send_open_house_reminders',
        event_id: eventId,
        nonce: '<?php echo wp_create_nonce('hph_ajax_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            alert('<?php esc_html_e('Reminders sent successfully!', 'happy-place'); ?>');
        } else {
            alert(response.data.message || '<?php esc_html_e('Error sending reminders.', 'happy-place'); ?>');
        }
    });
}

function viewAttendees(eventId) {
    // Open attendees modal or redirect
    alert('<?php esc_html_e('Attendees view feature coming soon!', 'happy-place'); ?>');
}

function duplicateEvent(eventId) {
    if (!confirm('<?php esc_html_e('Create a copy of this open house event?', 'happy-place'); ?>')) {
        return;
    }
    
    // AJAX call to duplicate event
    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'hph_duplicate_open_house',
        event_id: eventId,
        nonce: '<?php echo wp_create_nonce('hph_ajax_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.data.message || '<?php esc_html_e('Error duplicating event.', 'happy-place'); ?>');
        }
    });
}

function cancelEvent(eventId) {
    if (!confirm('<?php esc_html_e('Are you sure you want to cancel this open house? This will notify all registered attendees.', 'happy-place'); ?>')) {
        return;
    }
    
    // AJAX call to cancel event
    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'hph_cancel_open_house',
        event_id: eventId,
        nonce: '<?php echo wp_create_nonce('hph_ajax_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.data.message || '<?php esc_html_e('Error canceling event.', 'happy-place'); ?>');
        }
    });
}

function viewEventReport(eventId) {
    // Open event report modal or redirect
    alert('<?php esc_html_e('Event report feature coming soon!', 'happy-place'); ?>');
}
</script>

<style>
/* Open Houses Section Specific Styles */
.hph-dashboard-open-houses {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-6);
}

.hph-open-houses-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--hph-spacing-4);
}

.hph-open-houses-title-group h2 {
    margin: 0 0 var(--hph-spacing-2) 0;
    font-size: var(--hph-font-size-2xl);
    font-weight: var(--hph-font-bold);
}

.hph-open-houses-description {
    margin: 0;
    color: var(--hph-color-gray-600);
}

.hph-open-houses-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--hph-spacing-4);
}

.hph-view-toggle-container {
    display: flex;
    justify-content: center;
    margin-bottom: var(--hph-spacing-6);
}

.hph-view-toggle {
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
    gap: var(--hph-spacing-5);
}

.hph-event-card {
    background: var(--hph-color-white);
    border-radius: var(--hph-radius-xl);
    border: 1px solid var(--hph-color-gray-200);
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.hph-event-card:hover {
    border-color: var(--hph-color-primary-300);
    box-shadow: var(--hph-shadow-lg);
    transform: translateY(-2px);
}

.hph-event-card--past {
    opacity: 0.8;
}

.hph-event-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--hph-spacing-4);
    background: var(--hph-color-gray-25);
    border-bottom: 1px solid var(--hph-color-gray-200);
}

.hph-event-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: var(--hph-color-primary-500);
    color: white;
    border-radius: var(--hph-radius-md);
    padding: var(--hph-spacing-2);
    min-width: 50px;
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
}

.hph-event-status {
    padding: var(--hph-spacing-1) var(--hph-spacing-2);
    border-radius: var(--hph-radius-md);
    font-size: var(--hph-font-size-xs);
    font-weight: var(--hph-font-medium);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hph-event-status--scheduled {
    background: var(--hph-color-success-100);
    color: var(--hph-color-success-700);
}

.hph-event-status--completed {
    background: var(--hph-color-gray-200);
    color: var(--hph-color-gray-700);
}

.hph-event-status--cancelled {
    background: var(--hph-color-danger-100);
    color: var(--hph-color-danger-700);
}

.hph-event-card-content {
    padding: var(--hph-spacing-5);
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-3);
}

.hph-event-title {
    margin: 0;
    font-size: var(--hph-font-size-lg);
    font-weight: var(--hph-font-semibold);
    color: var(--hph-color-gray-900);
}

.hph-event-address {
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-2);
    color: var(--hph-color-gray-600);
    font-size: var(--hph-font-size-sm);
}

.hph-event-time,
.hph-event-attendees {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-2);
    color: var(--hph-color-gray-600);
    font-size: var(--hph-font-size-sm);
}

.hph-event-time i,
.hph-event-attendees i {
    color: var(--hph-color-gray-400);
    width: 16px;
}

.hph-event-card-actions {
    padding: var(--hph-spacing-4);
    border-top: 1px solid var(--hph-color-gray-200);
    display: flex;
    gap: var(--hph-spacing-2);
    align-items: center;
    background: var(--hph-color-gray-25);
}

.hph-event-actions-dropdown {
    position: relative;
    margin-left: auto;
}

@media (max-width: 768px) {
    .hph-open-houses-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .hph-open-houses-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .hph-events-grid {
        grid-template-columns: 1fr;
    }
    
    .hph-event-card-actions {
        flex-wrap: wrap;
    }
}
</style>
