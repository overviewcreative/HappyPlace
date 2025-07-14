<?php

/**
 * Dashboard Leads Section Template Part
 *
 * @package HappyPlace
 */

// Access the section data passed from the parent template
$section_data = $args['section_data'] ?? [];

// Get leads with fallbacks
$leads = $section_data['leads'] ?? [];
$filters = $section_data['filters'] ?? [];
$stats = $section_data['stats'] ?? [];

// Current filters
$status_filter = $_GET['status'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'date';
$sort_order = $_GET['order'] ?? 'desc';
?>

<div class="hph-dashboard-leads">
    <!-- Lead Stats -->
    <div class="hph-dashboard-stats">
        <div class="hph-dashboard-stat-card">
            <div class="hph-dashboard-stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="hph-dashboard-stat-content">
                <h3><?php echo esc_html($stats['total'] ?? 0); ?></h3>
                <p><?php _e('Total Leads', 'happy-place'); ?></p>
            </div>
        </div>

        <div class="hph-dashboard-stat-card">
            <div class="hph-dashboard-stat-icon">
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="hph-dashboard-stat-content">
                <h3><?php echo esc_html($stats['pending'] ?? 0); ?></h3>
                <p><?php _e('Pending', 'happy-place'); ?></p>
            </div>
        </div>

        <div class="hph-dashboard-stat-card">
            <div class="hph-dashboard-stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="hph-dashboard-stat-content">
                <h3><?php echo esc_html($stats['converted'] ?? 0); ?></h3>
                <p><?php _e('Converted', 'happy-place'); ?></p>
            </div>
        </div>
    </div>

    <!-- Leads Controls -->
    <div class="hph-dashboard-controls">
        <div class="hph-dashboard-filters">
            <select name="status" class="hph-select">
                <option value="all" <?php selected($status_filter, 'all'); ?>>
                    <?php _e('All Status', 'happy-place'); ?>
                </option>
                <option value="new" <?php selected($status_filter, 'new'); ?>>
                    <?php _e('New', 'happy-place'); ?>
                </option>
                <option value="contacted" <?php selected($status_filter, 'contacted'); ?>>
                    <?php _e('Contacted', 'happy-place'); ?>
                </option>
                <option value="qualified" <?php selected($status_filter, 'qualified'); ?>>
                    <?php _e('Qualified', 'happy-place'); ?>
                </option>
                <option value="converted" <?php selected($status_filter, 'converted'); ?>>
                    <?php _e('Converted', 'happy-place'); ?>
                </option>
                <option value="lost" <?php selected($status_filter, 'lost'); ?>>
                    <?php _e('Lost', 'happy-place'); ?>
                </option>
            </select>

            <select name="sort" class="hph-select">
                <option value="date" <?php selected($sort_by, 'date'); ?>>
                    <?php _e('Sort by Date', 'happy-place'); ?>
                </option>
                <option value="name" <?php selected($sort_by, 'name'); ?>>
                    <?php _e('Sort by Name', 'happy-place'); ?>
                </option>
                <option value="priority" <?php selected($sort_by, 'priority'); ?>>
                    <?php _e('Sort by Priority', 'happy-place'); ?>
                </option>
            </select>

            <select name="order" class="hph-select">
                <option value="desc" <?php selected($sort_order, 'desc'); ?>>
                    <?php _e('Descending', 'happy-place'); ?>
                </option>
                <option value="asc" <?php selected($sort_order, 'asc'); ?>>
                    <?php _e('Ascending', 'happy-place'); ?>
                </option>
            </select>
        </div>

        <a href="<?php echo esc_url(add_query_arg(['action' => 'new-lead'], get_permalink())); ?>" class="hph-button hph-button--primary">
            <i class="fas fa-plus"></i>
            <?php _e('Add New Lead', 'happy-place'); ?>
        </a>
    </div>

    <!-- Leads Table -->
    <div class="hph-dashboard-table-wrapper">
        <?php if (!empty($leads)) : ?>
            <table class="hph-dashboard-table">
                <thead>
                    <tr>
                        <th><?php _e('Lead', 'happy-place'); ?></th>
                        <th><?php _e('Status', 'happy-place'); ?></th>
                        <th><?php _e('Source', 'happy-place'); ?></th>
                        <th><?php _e('Priority', 'happy-place'); ?></th>
                        <th><?php _e('Last Contact', 'happy-place'); ?></th>
                        <th><?php _e('Actions', 'happy-place'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead) : ?>
                        <tr>
                            <td>
                                <div class="hph-lead-cell">
                                    <div class="hph-lead-info">
                                        <h4>
                                            <a href="<?php echo esc_url($lead['edit_url']); ?>">
                                                <?php echo esc_html($lead['name']); ?>
                                            </a>
                                        </h4>
                                        <p><?php echo esc_html($lead['email']); ?></p>
                                        <?php if (!empty($lead['phone'])) : ?>
                                            <p><?php echo esc_html($lead['phone']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="hph-status hph-status--<?php echo esc_attr($lead['status']); ?>">
                                    <?php echo esc_html($lead['status_label']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="hph-lead-source">
                                    <i class="<?php echo esc_attr($lead['source_icon']); ?>"></i>
                                    <?php echo esc_html($lead['source']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="hph-priority hph-priority--<?php echo esc_attr($lead['priority']); ?>">
                                    <?php echo esc_html($lead['priority_label']); ?>
                                </span>
                            </td>
                            <td>
                                <time datetime="<?php echo esc_attr($lead['last_contact']); ?>">
                                    <?php echo esc_html(human_time_diff(strtotime($lead['last_contact']), current_time('timestamp'))); ?> ago
                                </time>
                            </td>
                            <td>
                                <div class="hph-table-actions">
                                    <a href="<?php echo esc_url($lead['edit_url']); ?>"
                                        class="hph-button hph-button--icon"
                                        title="<?php esc_attr_e('Edit', 'happy-place'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button"
                                        class="hph-button hph-button--icon"
                                        data-action="quick-contact"
                                        data-id="<?php echo esc_attr($lead['id']); ?>"
                                        title="<?php esc_attr_e('Quick Contact', 'happy-place'); ?>">
                                        <i class="fas fa-comment"></i>
                                    </button>
                                    <button type="button"
                                        class="hph-button hph-button--icon hph-button--danger"
                                        data-action="delete"
                                        data-id="<?php echo esc_attr($lead['id']); ?>"
                                        title="<?php esc_attr_e('Delete', 'happy-place'); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="hph-no-content">
                <i class="fas fa-users"></i>
                <h3><?php _e('No Leads Found', 'happy-place'); ?></h3>
                <p><?php _e('Your leads will appear here once you start receiving inquiries.', 'happy-place'); ?></p>
                <a href="<?php echo esc_url(add_query_arg(['action' => 'new-lead'], get_permalink())); ?>" class="hph-button hph-button--primary">
                    <i class="fas fa-plus"></i>
                    <?php _e('Add New Lead', 'happy-place'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>