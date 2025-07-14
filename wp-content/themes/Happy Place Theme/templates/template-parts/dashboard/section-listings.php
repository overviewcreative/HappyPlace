<?php

/**
 * Dashboard Listings Section Template Part
 *
 * @package HappyPlace
 */

// Access the section data passed from the parent template
$section_data = $args['section_data'] ?? [];

// Get listings with fallbacks
$listings = $section_data['listings'] ?? [];
$filters = $section_data['filters'] ?? [];
$stats = $section_data['stats'] ?? [];

// Current filters
$status_filter = $_GET['status'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'date';
$sort_order = $_GET['order'] ?? 'desc';
?>

<div class="hph-dashboard-listings">
    <!-- Listing Stats -->
    <div class="hph-dashboard-stats">
        <div class="hph-dashboard-stat-card">
            <div class="hph-dashboard-stat-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="hph-dashboard-stat-content">
                <h3><?php echo esc_html($stats['total'] ?? 0); ?></h3>
                <p><?php _e('Total Listings', 'happy-place'); ?></p>
            </div>
        </div>

        <div class="hph-dashboard-stat-card">
            <div class="hph-dashboard-stat-icon">
                <i class="fas fa-eye"></i>
            </div>
            <div class="hph-dashboard-stat-content">
                <h3><?php echo esc_html($stats['views'] ?? 0); ?></h3>
                <p><?php _e('Total Views', 'happy-place'); ?></p>
            </div>
        </div>

        <div class="hph-dashboard-stat-card">
            <div class="hph-dashboard-stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="hph-dashboard-stat-content">
                <h3><?php echo esc_html($stats['conversions'] ?? 0); ?>%</h3>
                <p><?php _e('Conversion Rate', 'happy-place'); ?></p>
            </div>
        </div>
    </div>

    <!-- Listings Controls -->
    <div class="hph-dashboard-controls">
        <div class="hph-dashboard-filters">
            <select name="status" class="hph-select">
                <option value="all" <?php selected($status_filter, 'all'); ?>>
                    <?php _e('All Status', 'happy-place'); ?>
                </option>
                <option value="active" <?php selected($status_filter, 'active'); ?>>
                    <?php _e('Active', 'happy-place'); ?>
                </option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>>
                    <?php _e('Pending', 'happy-place'); ?>
                </option>
                <option value="sold" <?php selected($status_filter, 'sold'); ?>>
                    <?php _e('Sold', 'happy-place'); ?>
                </option>
            </select>

            <select name="sort" class="hph-select">
                <option value="date" <?php selected($sort_by, 'date'); ?>>
                    <?php _e('Sort by Date', 'happy-place'); ?>
                </option>
                <option value="price" <?php selected($sort_by, 'price'); ?>>
                    <?php _e('Sort by Price', 'happy-place'); ?>
                </option>
                <option value="views" <?php selected($sort_by, 'views'); ?>>
                    <?php _e('Sort by Views', 'happy-place'); ?>
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

        <a href="<?php echo esc_url(add_query_arg(['action' => 'new-listing'], get_permalink())); ?>" class="hph-btn hph-btn-primary">
            <i class="fas fa-plus"></i>
            <?php _e('Add New Listing', 'happy-place'); ?>
        </a>
    </div>

    <!-- Listings Table -->
    <div class="hph-dashboard-table-wrapper">
        <?php if (!empty($listings)) : ?>
            <table class="hph-dashboard-table">
                <thead>
                    <tr>
                        <th><?php _e('Listing', 'happy-place'); ?></th>
                        <th><?php _e('Status', 'happy-place'); ?></th>
                        <th><?php _e('Price', 'happy-place'); ?></th>
                        <th><?php _e('Views', 'happy-place'); ?></th>
                        <th><?php _e('Leads', 'happy-place'); ?></th>
                        <th><?php _e('Actions', 'happy-place'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listings as $listing) : ?>
                        <tr>
                            <td>
                                <div class="hph-listing-cell">
                                    <?php if (!empty($listing['thumbnail'])) : ?>
                                        <img src="<?php echo esc_url($listing['thumbnail']); ?>"
                                            alt="<?php echo esc_attr($listing['title']); ?>"
                                            class="hph-listing-thumbnail">
                                    <?php endif; ?>
                                    <div class="hph-listing-info">
                                        <h4>
                                            <a href="<?php echo esc_url($listing['edit_url']); ?>">
                                                <?php echo esc_html($listing['title']); ?>
                                            </a>
                                        </h4>
                                        <p><?php echo esc_html($listing['address']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="hph-status hph-status--<?php echo esc_attr($listing['status']); ?>">
                                    <?php echo esc_html($listing['status_label']); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($listing['price_formatted']); ?></td>
                            <td><?php echo esc_html($listing['views']); ?></td>
                            <td><?php echo esc_html($listing['leads']); ?></td>
                            <td>
                                <div class="hph-table-actions">
                                    <a href="<?php echo esc_url(add_query_arg(['action' => 'edit-listing', 'listing_id' => $listing['id']], get_permalink())); ?>"
                                        class="hph-btn hph-btn-sm"
                                        title="<?php esc_attr_e('Edit', 'happy-place'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo esc_url($listing['preview_url']); ?>"
                                        class="hph-button hph-button--icon"
                                        title="<?php esc_attr_e('Preview', 'happy-place'); ?>"
                                        target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button"
                                        class="hph-button hph-button--icon hph-button--danger"
                                        data-action="delete"
                                        data-id="<?php echo esc_attr($listing['id']); ?>"
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
                <i class="fas fa-home"></i>
                <h3><?php _e('No Listings Found', 'happy-place'); ?></h3>
                <p><?php _e('Get started by adding your first listing.', 'happy-place'); ?></p>
                <a href="<?php echo esc_url(add_query_arg(['action' => 'new-listing'], get_permalink())); ?>" class="hph-btn hph-btn-primary">
                    <i class="fas fa-plus"></i>
                    <?php _e('Add New Listing', 'happy-place'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>