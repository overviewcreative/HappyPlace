<?php
/**
 * Dashboard Leads Section Template
 * 
 * Displays and manages agent's leads with filtering, follow-up tracking, and communication
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get agent data (available from parent template)
$current_agent_id = $current_agent_id ?? get_current_user_id();

// Handle lead actions
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$lead_id = isset($_GET['lead_id']) ? intval($_GET['lead_id']) : 0;

// Process actions
if ($action && $lead_id) {
    switch ($action) {
        case 'mark_contacted':
            if (wp_verify_nonce($_GET['_wpnonce'], 'contact_lead_' . $lead_id)) {
                hph_update_lead_status($lead_id, 'contacted');
                wp_redirect(add_query_arg(['section' => 'leads', 'message' => 'contacted']));
                exit;
            }
            break;
        case 'mark_qualified':
            if (wp_verify_nonce($_GET['_wpnonce'], 'qualify_lead_' . $lead_id)) {
                hph_update_lead_status($lead_id, 'qualified');
                wp_redirect(add_query_arg(['section' => 'leads', 'message' => 'qualified']));
                exit;
            }
            break;
        case 'mark_closed':
            if (wp_verify_nonce($_GET['_wpnonce'], 'close_lead_' . $lead_id)) {
                hph_update_lead_status($lead_id, 'closed');
                wp_redirect(add_query_arg(['section' => 'leads', 'message' => 'closed']));
                exit;
            }
            break;
    }
}

// Handle note submission
if (isset($_POST['hph_add_lead_note']) && wp_verify_nonce($_POST['hph_lead_note_nonce'], 'add_lead_note')) {
    $result = hph_add_lead_note($_POST['lead_id'], $current_agent_id, $_POST['note_content']);
    if ($result) {
        echo '<script>document.addEventListener("DOMContentLoaded", function() { window.HphDashboard.showToast("Note added successfully!", "success"); });</script>';
    }
}

// Display success messages
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
if ($message) {
    $messages = [
        'contacted' => __('Lead marked as contacted.', 'happy-place'),
        'qualified' => __('Lead marked as qualified.', 'happy-place'),
        'closed' => __('Lead marked as closed.', 'happy-place'),
    ];
    
    if (isset($messages[$message])) {
        echo '<script>window.addEventListener("load", function() { window.HphDashboard.showToast("' . esc_js($messages[$message]) . '", "success"); });</script>';
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$source_filter = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';
$date_range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : '30';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get leads data
$leads = hph_get_agent_leads($current_agent_id, [
    'status' => $status_filter,
    'source' => $source_filter,
    'date_range' => $date_range,
    'search' => $search
]);

// Get lead statistics
$lead_stats = hph_get_lead_statistics($current_agent_id, $date_range);

// Lead sources and statuses for filters
$lead_sources = [
    'website' => __('Website Contact', 'happy-place'),
    'phone' => __('Phone Call', 'happy-place'),
    'social' => __('Social Media', 'happy-place'),
    'referral' => __('Referral', 'happy-place'),
    'open_house' => __('Open House', 'happy-place'),
    'email' => __('Email Inquiry', 'happy-place')
];

$lead_statuses = [
    'new' => __('New', 'happy-place'),
    'contacted' => __('Contacted', 'happy-place'),
    'qualified' => __('Qualified', 'happy-place'),
    'nurturing' => __('Nurturing', 'happy-place'),
    'closed' => __('Closed', 'happy-place'),
    'lost' => __('Lost', 'happy-place')
];
?>

<div class="hph-leads-section">
    
    <!-- Section Header -->
    <div class="hph-section-header hph-d-flex hph-justify-between hph-items-center hph-mb-6">
        <div>
            <h2 class="hph-text-2xl hph-font-bold hph-text-gray-900 hph-mb-2">
                <?php esc_html_e('Lead Management', 'happy-place'); ?>
            </h2>
            <p class="hph-text-gray-600">
                <?php 
                printf(
                    /* translators: %d: number of leads */
                    esc_html__('Manage and track your %d leads', 'happy-place'),
                    count($leads)
                ); 
                ?>
            </p>
        </div>
        
        <div class="hph-section-actions">
            <button class="hph-btn hph-btn--outline" data-action="export-leads">
                <i class="fas fa-download"></i>
                <?php esc_html_e('Export Leads', 'happy-place'); ?>
            </button>
            <button class="hph-btn hph-btn--primary" data-modal="add-lead">
                <i class="fas fa-plus"></i>
                <?php esc_html_e('Add Lead', 'happy-place'); ?>
            </button>
        </div>
    </div>

    <!-- Lead Statistics -->
    <div class="hph-lead-stats">
        <div class="hph-stat-card hph-stat-card--leads">
            <div class="hph-stat-header">
                <div class="hph-stat-icon hph-stat-icon--new">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h4 class="hph-stat-title"><?php esc_html_e('New Leads', 'happy-place'); ?></h4>
            </div>
            <div class="hph-stat-value"><?php echo esc_html($lead_stats['new'] ?? 0); ?></div>
            <div class="hph-stat-change hph-stat-change--positive">
                <i class="fas fa-arrow-up"></i>
                <?php echo esc_html(($lead_stats['new_change'] ?? 0) . '%'); ?>
            </div>
        </div>
        
        <div class="hph-stat-card hph-stat-card--leads">
            <div class="hph-stat-header">
                <div class="hph-stat-icon hph-stat-icon--contacted">
                    <i class="fas fa-phone"></i>
                </div>
                <h4 class="hph-stat-title"><?php esc_html_e('Contacted', 'happy-place'); ?></h4>
            </div>
            <div class="hph-stat-value"><?php echo esc_html($lead_stats['contacted'] ?? 0); ?></div>
            <div class="hph-stat-progress">
                <div class="hph-progress-bar">
                    <div class="hph-progress-fill" style="width: <?php echo esc_attr(($lead_stats['contacted_percentage'] ?? 0) . '%'); ?>"></div>
                </div>
                <span class="hph-progress-text"><?php echo esc_html(($lead_stats['contacted_percentage'] ?? 0) . '%'); ?></span>
            </div>
        </div>
        
        <div class="hph-stat-card hph-stat-card--leads">
            <div class="hph-stat-header">
                <div class="hph-stat-icon hph-stat-icon--qualified">
                    <i class="fas fa-star"></i>
                </div>
                <h4 class="hph-stat-title"><?php esc_html_e('Qualified', 'happy-place'); ?></h4>
            </div>
            <div class="hph-stat-value"><?php echo esc_html($lead_stats['qualified'] ?? 0); ?></div>
            <div class="hph-stat-progress">
                <div class="hph-progress-bar">
                    <div class="hph-progress-fill" style="width: <?php echo esc_attr(($lead_stats['qualified_percentage'] ?? 0) . '%'); ?>"></div>
                </div>
                <span class="hph-progress-text"><?php echo esc_html(($lead_stats['qualified_percentage'] ?? 0) . '%'); ?></span>
            </div>
        </div>
        
        <div class="hph-stat-card hph-stat-card--leads">
            <div class="hph-stat-header">
                <div class="hph-stat-icon hph-stat-icon--conversion">
                    <i class="fas fa-handshake"></i>
                </div>
                <h4 class="hph-stat-title"><?php esc_html_e('Conversion Rate', 'happy-place'); ?></h4>
            </div>
            <div class="hph-stat-value"><?php echo esc_html(($lead_stats['conversion_rate'] ?? 0) . '%'); ?></div>
            <div class="hph-stat-change hph-stat-change--<?php echo ($lead_stats['conversion_change'] ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                <i class="fas fa-arrow-<?php echo ($lead_stats['conversion_change'] ?? 0) >= 0 ? 'up' : 'down'; ?>"></i>
                <?php echo esc_html(abs($lead_stats['conversion_change'] ?? 0) . '%'); ?>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="hph-leads-toolbar">
        <form method="GET" class="hph-leads-filter-form">
            <input type="hidden" name="section" value="leads">
            
            <!-- Search Input -->
            <div class="hph-filter-group">
                <label for="search" class="hph-filter-label"><?php esc_html_e('Search', 'happy-place'); ?></label>
                <div class="hph-search-wrapper">
                    <input type="text" 
                           id="search"
                           name="search" 
                           value="<?php echo esc_attr($search); ?>"
                           placeholder="<?php esc_attr_e('Search leads...', 'happy-place'); ?>"
                           class="hph-search-input">
                    <i class="fas fa-search hph-search-icon"></i>
                </div>
            </div>

            <!-- Status Filter -->
            <div class="hph-filter-group">
                <label for="status" class="hph-filter-label"><?php esc_html_e('Status', 'happy-place'); ?></label>
                <select name="status" id="status" class="hph-filter-select">
                    <option value=""><?php esc_html_e('All Statuses', 'happy-place'); ?></option>
                    <?php foreach ($lead_statuses as $status_key => $status_label) : ?>
                        <option value="<?php echo esc_attr($status_key); ?>" <?php selected($status_filter, $status_key); ?>>
                            <?php echo esc_html($status_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Source Filter -->
            <div class="hph-filter-group">
                <label for="source" class="hph-filter-label"><?php esc_html_e('Source', 'happy-place'); ?></label>
                <select name="source" id="source" class="hph-filter-select">
                    <option value=""><?php esc_html_e('All Sources', 'happy-place'); ?></option>
                    <?php foreach ($lead_sources as $source_key => $source_label) : ?>
                        <option value="<?php echo esc_attr($source_key); ?>" <?php selected($source_filter, $source_key); ?>>
                            <?php echo esc_html($source_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date Range Filter -->
            <div class="hph-filter-group">
                <label for="range" class="hph-filter-label"><?php esc_html_e('Period', 'happy-place'); ?></label>
                <select name="range" id="range" class="hph-filter-select">
                    <option value="7" <?php selected($date_range, '7'); ?>><?php esc_html_e('Last 7 days', 'happy-place'); ?></option>
                    <option value="30" <?php selected($date_range, '30'); ?>><?php esc_html_e('Last 30 days', 'happy-place'); ?></option>
                    <option value="90" <?php selected($date_range, '90'); ?>><?php esc_html_e('Last 3 months', 'happy-place'); ?></option>
                    <option value="365" <?php selected($date_range, '365'); ?>><?php esc_html_e('Last year', 'happy-place'); ?></option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="hph-filter-group">
                <button type="submit" class="hph-btn hph-btn--primary">
                    <i class="fas fa-filter"></i>
                    <?php esc_html_e('Filter', 'happy-place'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Leads List -->
    <?php if (!empty($leads)) : ?>
        <div class="hph-leads-list">
            <?php foreach ($leads as $lead) : 
                $lead_id = $lead['ID'];
                $days_since_contact = $lead['days_since_contact'] ?? 0;
                $urgency_class = '';
                
                if ($lead['status'] === 'new') {
                    $urgency_class = 'hph-lead-card--urgent';
                } elseif ($days_since_contact > 7) {
                    $urgency_class = 'hph-lead-card--overdue';
                } elseif ($days_since_contact > 3) {
                    $urgency_class = 'hph-lead-card--attention';
                }
            ?>
                <div class="hph-lead-card <?php echo esc_attr($urgency_class); ?>" data-lead-id="<?php echo esc_attr($lead_id); ?>">
                    
                    <!-- Lead Header -->
                    <div class="hph-lead-header">
                        <div class="hph-lead-avatar">
                            <?php if (!empty($lead['avatar'])) : ?>
                                <img src="<?php echo esc_url($lead['avatar']); ?>" 
                                     alt="<?php echo esc_attr($lead['name']); ?>" 
                                     class="hph-lead-avatar-img">
                            <?php else : ?>
                                <div class="hph-lead-avatar-placeholder">
                                    <?php echo esc_html(strtoupper(substr($lead['name'], 0, 1))); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="hph-lead-info">
                            <h3 class="hph-lead-name">
                                <a href="#" data-action="view-lead" data-lead-id="<?php echo esc_attr($lead_id); ?>">
                                    <?php echo esc_html($lead['name']); ?>
                                </a>
                            </h3>
                            <div class="hph-lead-contact">
                                <?php if (!empty($lead['email'])) : ?>
                                    <a href="mailto:<?php echo esc_attr($lead['email']); ?>" class="hph-lead-email">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo esc_html($lead['email']); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($lead['phone'])) : ?>
                                    <a href="tel:<?php echo esc_attr($lead['phone']); ?>" class="hph-lead-phone">
                                        <i class="fas fa-phone"></i>
                                        <?php echo esc_html($lead['phone']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="hph-lead-status-info">
                            <span class="hph-lead-status hph-lead-status--<?php echo esc_attr($lead['status']); ?>">
                                <?php echo esc_html($lead_statuses[$lead['status']] ?? ucfirst($lead['status'])); ?>
                            </span>
                            <div class="hph-lead-source">
                                <i class="fas fa-tag"></i>
                                <?php echo esc_html($lead_sources[$lead['source']] ?? ucfirst($lead['source'])); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Lead Details -->
                    <div class="hph-lead-details">
                        <div class="hph-lead-meta">
                            <div class="hph-lead-meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo esc_html(human_time_diff(strtotime($lead['created_date']), current_time('timestamp'))); ?> ago</span>
                            </div>
                            
                            <?php if (!empty($lead['interested_property'])) : ?>
                                <div class="hph-lead-meta-item">
                                    <i class="fas fa-home"></i>
                                    <span><?php echo esc_html($lead['interested_property']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($days_since_contact > 0) : ?>
                                <div class="hph-lead-meta-item hph-lead-meta-item--<?php echo $days_since_contact > 7 ? 'urgent' : ($days_since_contact > 3 ? 'warning' : 'normal'); ?>">
                                    <i class="fas fa-clock"></i>
                                    <span>
                                        <?php 
                                        printf(
                                            /* translators: %d: number of days */
                                            esc_html__('Last contact: %d days ago', 'happy-place'),
                                            $days_since_contact
                                        ); 
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($lead['message'])) : ?>
                            <div class="hph-lead-message">
                                <p><?php echo esc_html(wp_trim_words($lead['message'], 20)); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="hph-lead-actions">
                        <div class="hph-lead-quick-actions">
                            <button class="hph-quick-action-btn hph-quick-action-btn--call" 
                                    data-action="call" 
                                    data-phone="<?php echo esc_attr($lead['phone']); ?>"
                                    title="<?php esc_attr_e('Call Lead', 'happy-place'); ?>">
                                <i class="fas fa-phone"></i>
                            </button>
                            
                            <button class="hph-quick-action-btn hph-quick-action-btn--email" 
                                    data-action="email" 
                                    data-email="<?php echo esc_attr($lead['email']); ?>"
                                    title="<?php esc_attr_e('Send Email', 'happy-place'); ?>">
                                <i class="fas fa-envelope"></i>
                            </button>
                            
                            <button class="hph-quick-action-btn hph-quick-action-btn--note" 
                                    data-action="add-note" 
                                    data-lead-id="<?php echo esc_attr($lead_id); ?>"
                                    title="<?php esc_attr_e('Add Note', 'happy-place'); ?>">
                                <i class="fas fa-sticky-note"></i>
                            </button>
                        </div>
                        
                        <div class="hph-lead-status-actions">
                            <?php if ($lead['status'] === 'new') : ?>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'mark_contacted', 'lead_id' => $lead_id]), 'contact_lead_' . $lead_id)); ?>" 
                                   class="hph-btn hph-btn--sm hph-btn--primary">
                                    <i class="fas fa-check"></i>
                                    <?php esc_html_e('Mark Contacted', 'happy-place'); ?>
                                </a>
                            <?php elseif ($lead['status'] === 'contacted') : ?>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'mark_qualified', 'lead_id' => $lead_id]), 'qualify_lead_' . $lead_id)); ?>" 
                                   class="hph-btn hph-btn--sm hph-btn--primary">
                                    <i class="fas fa-star"></i>
                                    <?php esc_html_e('Mark Qualified', 'happy-place'); ?>
                                </a>
                            <?php elseif (in_array($lead['status'], ['qualified', 'nurturing'])) : ?>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'mark_closed', 'lead_id' => $lead_id]), 'close_lead_' . $lead_id)); ?>" 
                                   class="hph-btn hph-btn--sm hph-btn--primary">
                                    <i class="fas fa-handshake"></i>
                                    <?php esc_html_e('Mark Closed', 'happy-place'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="hph-dropdown">
                            <button class="hph-btn hph-btn--sm hph-btn--secondary hph-dropdown-toggle">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <div class="hph-dropdown-menu">
                                <a href="#" class="hph-dropdown-item" data-action="view-lead">
                                    <i class="fas fa-eye"></i> <?php esc_html_e('View Details', 'happy-place'); ?>
                                </a>
                                <a href="#" class="hph-dropdown-item" data-action="edit-lead">
                                    <i class="fas fa-edit"></i> <?php esc_html_e('Edit Lead', 'happy-place'); ?>
                                </a>
                                <a href="#" class="hph-dropdown-item" data-action="schedule-followup">
                                    <i class="fas fa-calendar-plus"></i> <?php esc_html_e('Schedule Follow-up', 'happy-place'); ?>
                                </a>
                                <div class="hph-dropdown-divider"></div>
                                <a href="#" class="hph-dropdown-item hph-dropdown-item--danger" data-action="delete-lead">
                                    <i class="fas fa-trash"></i> <?php esc_html_e('Delete Lead', 'happy-place'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Preview -->
                    <?php if (!empty($lead['recent_notes'])) : ?>
                        <div class="hph-lead-notes-preview">
                            <div class="hph-lead-notes-header">
                                <h4 class="hph-lead-notes-title">
                                    <i class="fas fa-comments"></i>
                                    <?php esc_html_e('Recent Notes', 'happy-place'); ?>
                                </h4>
                                <button class="hph-btn hph-btn--sm hph-btn--outline" data-action="view-all-notes">
                                    <?php esc_html_e('View All', 'happy-place'); ?>
                                </button>
                            </div>
                            <div class="hph-lead-notes-list">
                                <?php foreach (array_slice($lead['recent_notes'], 0, 2) as $note) : ?>
                                    <div class="hph-lead-note">
                                        <div class="hph-lead-note-content">
                                            <?php echo esc_html(wp_trim_words($note['content'], 15)); ?>
                                        </div>
                                        <div class="hph-lead-note-meta">
                                            <?php echo esc_html(human_time_diff(strtotime($note['created_date']), current_time('timestamp'))); ?> ago
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="hph-pagination-wrapper">
            <!-- Add pagination here if needed -->
        </div>

    <?php else : ?>
        
        <!-- Empty State -->
        <div class="hph-empty-state">
            <div class="hph-empty-state-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3 class="hph-empty-state-title">
                <?php echo $search || $status_filter || $source_filter ? 
                    esc_html__('No leads found', 'happy-place') : 
                    esc_html__('No leads yet', 'happy-place'); ?>
            </h3>
            <p class="hph-empty-state-description">
                <?php if ($search || $status_filter || $source_filter) : ?>
                    <?php esc_html_e('Try adjusting your filters to find what you\'re looking for.', 'happy-place'); ?>
                <?php else : ?>
                    <?php esc_html_e('Start building your client base by adding your first lead or generating leads through your listings.', 'happy-place'); ?>
                <?php endif; ?>
            </p>
            <div class="hph-empty-state-actions">
                <?php if ($search || $status_filter || $source_filter) : ?>
                    <a href="<?php echo esc_url(add_query_arg('section', 'leads')); ?>" class="hph-btn hph-btn--secondary">
                        <i class="fas fa-times"></i>
                        <?php esc_html_e('Clear Filters', 'happy-place'); ?>
                    </a>
                <?php endif; ?>
                <button class="hph-btn hph-btn--primary" data-modal="add-lead">
                    <i class="fas fa-plus"></i>
                    <?php esc_html_e('Add Your First Lead', 'happy-place'); ?>
                </button>
            </div>
        </div>

    <?php endif; ?>

</div>

<!-- Add Note Modal -->
<div class="hph-modal-overlay" id="add-note-modal">
    <div class="hph-modal hph-modal--md">
        <div class="hph-modal-header">
            <h3 class="hph-modal-title">
                <i class="fas fa-sticky-note"></i>
                <?php esc_html_e('Add Note', 'happy-place'); ?>
            </h3>
            <button class="hph-modal-close" data-dismiss="modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="" class="hph-add-note-form">
            <?php wp_nonce_field('add_lead_note', 'hph_lead_note_nonce'); ?>
            <input type="hidden" name="lead_id" id="note-lead-id" value="">
            
            <div class="hph-modal-body">
                <div class="hph-form-group">
                    <label for="note_content" class="hph-form-label hph-form-label--required">
                        <?php esc_html_e('Note Content', 'happy-place'); ?>
                    </label>
                    <textarea id="note_content" 
                              name="note_content" 
                              class="hph-form-textarea" 
                              rows="5"
                              placeholder="<?php esc_attr_e('Add your note about this lead...', 'happy-place'); ?>"
                              required></textarea>
                </div>
            </div>
            
            <div class="hph-modal-footer">
                <button type="button" class="hph-btn hph-btn--secondary" data-dismiss="modal">
                    <?php esc_html_e('Cancel', 'happy-place'); ?>
                </button>
                <button type="submit" name="hph_add_lead_note" class="hph-btn hph-btn--primary">
                    <i class="fas fa-save"></i>
                    <?php esc_html_e('Add Note', 'happy-place'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Leads Section Specific Styles */
.hph-lead-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--hph-spacing-6);
    margin-bottom: var(--hph-spacing-8);
}

.hph-stat-card--leads {
    background: linear-gradient(135deg, var(--hph-color-white), var(--hph-color-gray-25));
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-xl);
    padding: var(--hph-spacing-5);
    transition: all var(--hph-transition-base);
    box-shadow: var(--hph-shadow-sm);
}

.hph-stat-card--leads:hover {
    transform: translateY(-2px);
    box-shadow: var(--hph-shadow-md);
}

.hph-stat-header {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-3);
    margin-bottom: var(--hph-spacing-4);
}

.hph-stat-icon--new { background: linear-gradient(135deg, #e0f2fe, #b3e5fc); color: #0277bd; }
.hph-stat-icon--contacted { background: linear-gradient(135deg, #f3e5f5, #e1bee7); color: #7b1fa2; }
.hph-stat-icon--qualified { background: linear-gradient(135deg, #fff3e0, #ffcc02); color: #f57c00; }
.hph-stat-icon--conversion { background: linear-gradient(135deg, #e8f5e8, #c8e6c9); color: #388e3c; }

.hph-stat-progress {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-3);
    margin-top: var(--hph-spacing-2);
}

.hph-progress-bar {
    flex: 1;
    height: 6px;
    background: var(--hph-color-gray-200);
    border-radius: var(--hph-radius-full);
    overflow: hidden;
}

.hph-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--hph-color-primary-400), var(--hph-color-primary-600));
    border-radius: var(--hph-radius-full);
    transition: width var(--hph-transition-base);
}

.hph-progress-text {
    font-size: var(--hph-font-size-xs);
    font-weight: var(--hph-font-semibold);
    color: var(--hph-color-primary-600);
}

.hph-leads-toolbar {
    background: var(--hph-color-white);
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-xl);
    padding: var(--hph-spacing-5);
    margin-bottom: var(--hph-spacing-6);
    box-shadow: var(--hph-shadow-sm);
}

.hph-leads-filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--hph-spacing-4);
    align-items: end;
}

.hph-filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-2);
}

.hph-filter-label {
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-medium);
    color: var(--hph-color-gray-700);
}

.hph-leads-list {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-6);
}

.hph-lead-card {
    background: var(--hph-color-white);
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-xl);
    overflow: hidden;
    transition: all var(--hph-transition-base);
    box-shadow: var(--hph-shadow-sm);
    position: relative;
}

.hph-lead-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--hph-color-gray-300);
    transition: background var(--hph-transition-base);
}

.hph-lead-card--urgent::before { background: var(--hph-color-danger); }
.hph-lead-card--overdue::before { background: var(--hph-color-warning); }
.hph-lead-card--attention::before { background: var(--hph-color-primary-400); }

.hph-lead-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--hph-shadow-lg);
    border-color: var(--hph-color-primary-200);
}

.hph-lead-header {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-4);
    padding: var(--hph-spacing-5);
    background: linear-gradient(135deg, var(--hph-color-gray-25), var(--hph-color-white));
    border-bottom: 1px solid var(--hph-color-gray-100);
}

.hph-lead-avatar {
    width: 60px;
    height: 60px;
    border-radius: var(--hph-radius-full);
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: var(--hph-shadow-sm);
}

.hph-lead-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hph-lead-avatar-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--hph-color-primary-400), var(--hph-color-primary-600));
    color: var(--hph-color-white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--hph-font-size-xl);
    font-weight: var(--hph-font-bold);
}

.hph-lead-info {
    flex: 1;
    min-width: 0;
}

.hph-lead-name {
    margin: 0 0 var(--hph-spacing-2);
    font-size: var(--hph-font-size-lg);
    font-weight: var(--hph-font-semibold);
}

.hph-lead-name a {
    color: var(--hph-color-gray-900);
    text-decoration: none;
    transition: color var(--hph-transition-fast);
}

.hph-lead-name a:hover {
    color: var(--hph-color-primary-600);
}

.hph-lead-contact {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-1);
}

.hph-lead-email,
.hph-lead-phone {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-2);
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-600);
    text-decoration: none;
    transition: color var(--hph-transition-fast);
}

.hph-lead-email:hover,
.hph-lead-phone:hover {
    color: var(--hph-color-primary-600);
}

.hph-lead-status-info {
    text-align: right;
    flex-shrink: 0;
}

.hph-lead-status {
    display: inline-block;
    padding: var(--hph-spacing-1) var(--hph-spacing-3);
    border-radius: var(--hph-radius-full);
    font-size: var(--hph-font-size-xs);
    font-weight: var(--hph-font-medium);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--hph-spacing-2);
}

.hph-lead-status--new { background: var(--hph-color-primary-100); color: var(--hph-color-primary-800); }
.hph-lead-status--contacted { background: var(--hph-color-warning-light); color: var(--hph-color-warning-dark); }
.hph-lead-status--qualified { background: var(--hph-color-success-light); color: var(--hph-color-success-dark); }
.hph-lead-status--nurturing { background: var(--hph-color-primary-100); color: var(--hph-color-primary-700); }
.hph-lead-status--closed { background: var(--hph-color-success-light); color: var(--hph-color-success-dark); }
.hph-lead-status--lost { background: var(--hph-color-gray-100); color: var(--hph-color-gray-600); }

.hph-lead-source {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-1);
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-500);
}

.hph-lead-details {
    padding: var(--hph-spacing-5);
}

.hph-lead-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--hph-spacing-4);
    margin-bottom: var(--hph-spacing-4);
}

.hph-lead-meta-item {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-1);
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-600);
}

.hph-lead-meta-item--urgent { color: var(--hph-color-danger); }
.hph-lead-meta-item--warning { color: var(--hph-color-warning-dark); }
.hph-lead-meta-item--normal { color: var(--hph-color-gray-600); }

.hph-lead-message {
    background: var(--hph-color-gray-25);
    padding: var(--hph-spacing-3);
    border-radius: var(--hph-radius-lg);
    border-left: 3px solid var(--hph-color-primary-300);
}

.hph-lead-message p {
    margin: 0;
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-700);
    line-height: 1.5;
}

.hph-lead-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--hph-spacing-4) var(--hph-spacing-5);
    border-top: 1px solid var(--hph-color-gray-100);
    background: var(--hph-color-gray-25);
    gap: var(--hph-spacing-4);
}

.hph-lead-quick-actions {
    display: flex;
    gap: var(--hph-spacing-2);
}

.hph-quick-action-btn {
    width: 36px;
    height: 36px;
    border: 1px solid var(--hph-color-gray-300);
    background: var(--hph-color-white);
    border-radius: var(--hph-radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all var(--hph-transition-fast);
    font-size: var(--hph-font-size-sm);
}

.hph-quick-action-btn:hover {
    transform: scale(1.1);
    box-shadow: var(--hph-shadow-sm);
}

.hph-quick-action-btn--call { color: var(--hph-color-success); }
.hph-quick-action-btn--call:hover { background: var(--hph-color-success-light); border-color: var(--hph-color-success); }

.hph-quick-action-btn--email { color: var(--hph-color-primary-600); }
.hph-quick-action-btn--email:hover { background: var(--hph-color-primary-50); border-color: var(--hph-color-primary-400); }

.hph-quick-action-btn--note { color: var(--hph-color-warning-dark); }
.hph-quick-action-btn--note:hover { background: var(--hph-color-warning-light); border-color: var(--hph-color-warning); }

.hph-lead-status-actions {
    display: flex;
    gap: var(--hph-spacing-2);
}

.hph-lead-notes-preview {
    padding: var(--hph-spacing-4) var(--hph-spacing-5) var(--hph-spacing-5);
    border-top: 1px solid var(--hph-color-gray-100);
    background: var(--hph-color-gray-25);
}

.hph-lead-notes-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--hph-spacing-3);
}

.hph-lead-notes-title {
    font-size: var(--hph-font-size-sm);
    font-weight: var(--hph-font-semibold);
    color: var(--hph-color-gray-900);
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-2);
}

.hph-lead-notes-list {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-2);
}

.hph-lead-note {
    background: var(--hph-color-white);
    padding: var(--hph-spacing-3);
    border-radius: var(--hph-radius-md);
    border-left: 3px solid var(--hph-color-primary-300);
}

.hph-lead-note-content {
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-700);
    margin-bottom: var(--hph-spacing-1);
    line-height: 1.4;
}

.hph-lead-note-meta {
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-500);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hph-lead-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: var(--hph-spacing-4);
    }
    
    .hph-leads-filter-form {
        grid-template-columns: 1fr;
        gap: var(--hph-spacing-3);
    }
    
    .hph-lead-header {
        flex-direction: column;
        text-align: center;
        gap: var(--hph-spacing-3);
    }
    
    .hph-lead-meta {
        flex-direction: column;
        gap: var(--hph-spacing-2);
    }
    
    .hph-lead-actions {
        flex-direction: column;
        gap: var(--hph-spacing-3);
        align-items: stretch;
    }
    
    .hph-lead-quick-actions {
        justify-content: center;
    }
    
    .hph-lead-status-actions {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .hph-lead-stats {
        grid-template-columns: 1fr;
    }
    
    .hph-lead-contact {
        align-items: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality for add note
    const addNoteModal = document.getElementById('add-note-modal');
    const noteModalCloses = addNoteModal.querySelectorAll('[data-dismiss="modal"]');
    
    // Quick action handlers
    document.addEventListener('click', function(e) {
        const action = e.target.closest('[data-action]');
        if (!action) return;
        
        const actionType = action.dataset.action;
        
        switch (actionType) {
            case 'call':
                const phone = action.dataset.phone;
                if (phone) {
                    window.location.href = `tel:${phone}`;
                }
                break;
                
            case 'email':
                const email = action.dataset.email;
                if (email) {
                    window.location.href = `mailto:${email}`;
                }
                break;
                
            case 'add-note':
                const leadId = action.dataset.leadId;
                if (leadId) {
                    document.getElementById('note-lead-id').value = leadId;
                    addNoteModal.classList.add('hph-modal-overlay--active');
                    document.body.classList.add('hph-modal-open');
                }
                break;
                
            case 'view-lead':
            case 'edit-lead':
            case 'schedule-followup':
            case 'view-all-notes':
            case 'delete-lead':
            case 'export-leads':
                // Placeholder actions
                if (window.HphDashboard && window.HphDashboard.showToast) {
                    window.HphDashboard.showToast('Feature coming soon!', 'info');
                }
                break;
        }
    });
    
    // Close modal handlers
    noteModalCloses.forEach(close => {
        close.addEventListener('click', function() {
            addNoteModal.classList.remove('hph-modal-overlay--active');
            document.body.classList.remove('hph-modal-open');
        });
    });
    
    // Close modal on overlay click
    addNoteModal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('hph-modal-overlay--active');
            document.body.classList.remove('hph-modal-open');
        }
    });
    
    // Auto-submit filter form on select change
    const filterSelects = document.querySelectorAll('.hph-filter-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });
});
</script>

<?php
/**
 * Helper functions for leads management
 */

function hph_get_agent_leads($agent_id, $filters = []) {
    // This is a placeholder - implement your actual data retrieval
    return [
        [
            'ID' => 1,
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'phone' => '(555) 123-4567',
            'status' => 'new',
            'source' => 'website',
            'created_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'days_since_contact' => 2,
            'message' => 'Interested in the property on Main Street. Please contact me.',
            'interested_property' => '123 Main Street',
            'recent_notes' => []
        ],
        [
            'ID' => 2,
            'name' => 'Sarah Johnson',
            'email' => 'sarah@example.com',
            'phone' => '(555) 987-6543',
            'status' => 'contacted',
            'source' => 'phone',
            'created_date' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'days_since_contact' => 1,
            'message' => 'Looking for a 3-bedroom home under $500k.',
            'interested_property' => '',
            'recent_notes' => [
                [
                    'content' => 'Called and left voicemail',
                    'created_date' => date('Y-m-d H:i:s', strtotime('-1 day'))
                ]
            ]
        ]
    ];
}

function hph_get_lead_statistics($agent_id, $date_range) {
    // This is a placeholder - implement your actual statistics calculation
    return [
        'new' => 5,
        'new_change' => 25,
        'contacted' => 12,
        'contacted_percentage' => 75,
        'qualified' => 8,
        'qualified_percentage' => 67,
        'conversion_rate' => 15,
        'conversion_change' => 5
    ];
}

function hph_update_lead_status($lead_id, $status) {
    // Implement status update logic
    return update_post_meta($lead_id, 'lead_status', $status);
}

function hph_add_lead_note($lead_id, $agent_id, $content) {
    // Implement note addition logic
    $note = [
        'content' => sanitize_textarea_field($content),
        'agent_id' => $agent_id,
        'created_date' => current_time('mysql')
    ];
    
    $existing_notes = get_post_meta($lead_id, 'lead_notes', true) ?: [];
    $existing_notes[] = $note;
    
    return update_post_meta($lead_id, 'lead_notes', $existing_notes);
}
?>