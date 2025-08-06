<?php
/**
 * Dashboard Listings Section Template
 * 
 * Displays and manages agent's property listings with search, filters, and actions
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get agent data (available from parent template)
$current_agent_id = $current_agent_id ?? get_current_user_id();

// Handle listing actions
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$listing_id = isset($_GET['listing_id']) ? intval($_GET['listing_id']) : 0;

// Process actions
if ($action && $listing_id) {
    switch ($action) {
        case 'delete':
            if (current_user_can('delete_post', $listing_id) && wp_verify_nonce($_GET['_wpnonce'], 'delete_listing_' . $listing_id)) {
                wp_delete_post($listing_id, true);
                wp_redirect(add_query_arg(['section' => 'listings', 'message' => 'deleted']));
                exit;
            }
            break;
        case 'duplicate':
            if (current_user_can('edit_posts') && wp_verify_nonce($_GET['_wpnonce'], 'duplicate_listing_' . $listing_id)) {
                $new_listing_id = hph_duplicate_listing($listing_id);
                wp_redirect(add_query_arg(['section' => 'listings', 'message' => 'duplicated', 'new_id' => $new_listing_id]));
                exit;
            }
            break;
        case 'toggle_status':
            if (current_user_can('edit_post', $listing_id) && wp_verify_nonce($_GET['_wpnonce'], 'toggle_status_' . $listing_id)) {
                $current_status = get_field('listing_status', $listing_id);
                $new_status = $current_status === 'active' ? 'inactive' : 'active';
                update_field('listing_status', $new_status, $listing_id);
                wp_redirect(add_query_arg(['section' => 'listings', 'message' => 'status_updated']));
                exit;
            }
            break;
    }
}

// Display success messages
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
if ($message) {
    $messages = [
        'deleted' => __('Listing deleted successfully.', 'happy-place'),
        'duplicated' => __('Listing duplicated successfully.', 'happy-place'),
        'status_updated' => __('Listing status updated successfully.', 'happy-place'),
    ];
    
    if (isset($messages[$message])) {
        echo '<script>window.addEventListener("load", function() { window.HphDashboard.showToast("' . esc_js($messages[$message]) . '", "success"); });</script>';
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$property_type = isset($_GET['property_type']) ? sanitize_text_field($_GET['property_type']) : '';
$sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'date_desc';

// Build query arguments
$query_args = [
    'author' => $current_agent_id,
    'post_type' => 'listing',
    'post_status' => ['publish', 'pending', 'draft'],
    'posts_per_page' => 20,
    'paged' => get_query_var('paged') ?: 1,
    'meta_query' => []
];

// Add search
if ($search) {
    $query_args['s'] = $search;
}

// Add status filter
if ($status_filter) {
    $query_args['meta_query'][] = [
        'key' => 'listing_status',
        'value' => $status_filter,
        'compare' => '='
    ];
}

// Add property type filter
if ($property_type) {
    $query_args['meta_query'][] = [
        'key' => 'property_type',
        'value' => $property_type,
        'compare' => '='
    ];
}

// Add sorting
switch ($sort_by) {
    case 'price_asc':
        $query_args['meta_key'] = 'listing_price';
        $query_args['orderby'] = 'meta_value_num';
        $query_args['order'] = 'ASC';
        break;
    case 'price_desc':
        $query_args['meta_key'] = 'listing_price';
        $query_args['orderby'] = 'meta_value_num';
        $query_args['order'] = 'DESC';
        break;
    case 'title_asc':
        $query_args['orderby'] = 'title';
        $query_args['order'] = 'ASC';
        break;
    case 'views_desc':
        $query_args['meta_key'] = 'listing_views';
        $query_args['orderby'] = 'meta_value_num';
        $query_args['order'] = 'DESC';
        break;
    default: // date_desc
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
        break;
}

// Execute query
$listings_query = new WP_Query($query_args);

// Get available filter options
$property_types = get_terms(['taxonomy' => 'property_type', 'hide_empty' => false]);
$listing_statuses = [
    'active' => __('Active', 'happy-place'),
    'pending' => __('Pending', 'happy-place'),
    'sold' => __('Sold', 'happy-place'),
    'inactive' => __('Inactive', 'happy-place')
];
?>

<div class="hph-listings-section">
    
    <!-- Section Header -->
    <div class="hph-section-header hph-d-flex hph-justify-between hph-items-center hph-mb-6">
        <div>
            <h2 class="hph-text-2xl hph-font-bold hph-text-gray-900 hph-mb-2">
                <?php esc_html_e('My Listings', 'happy-place'); ?>
            </h2>
            <p class="hph-text-gray-600">
                <?php printf(
                    /* translators: %d: number of listings */
                    esc_html__('Manage your %d property listings', 'happy-place'),
                    $listings_query->found_posts
                ); ?>
            </p>
        </div>
        
        <div class="hph-section-actions">
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=listing')); ?>" class="hph-btn hph-btn--primary">
                <i class="fas fa-plus"></i>
                <?php esc_html_e('Add New Listing', 'happy-place'); ?>
            </a>
        </div>
    </div>

    <!-- Filters and Search Toolbar -->
    <div class="hph-listings-toolbar">
        <form method="GET" class="hph-listings-search hph-d-flex hph-gap-3">
            <input type="hidden" name="section" value="listings">
            
            <!-- Search Input -->
            <div class="hph-search-wrapper hph-flex-1">
                <input type="text" 
                       name="search" 
                       value="<?php echo esc_attr($search); ?>"
                       placeholder="<?php esc_attr_e('Search listings...', 'happy-place'); ?>"
                       class="hph-search-input">
                <i class="fas fa-search hph-search-icon"></i>
            </div>

            <!-- Status Filter -->
            <div class="hph-filter-dropdown">
                <select name="status" class="hph-filter-select">
                    <option value=""><?php esc_html_e('All Statuses', 'happy-place'); ?></option>
                    <?php foreach ($listing_statuses as $status_key => $status_label) : ?>
                        <option value="<?php echo esc_attr($status_key); ?>" <?php selected($status_filter, $status_key); ?>>
                            <?php echo esc_html($status_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Property Type Filter -->
            <?php if (!empty($property_types)) : ?>
            <div class="hph-filter-dropdown">
                <select name="property_type" class="hph-filter-select">
                    <option value=""><?php esc_html_e('All Types', 'happy-place'); ?></option>
                    <?php foreach ($property_types as $type) : ?>
                        <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($property_type, $type->slug); ?>>
                            <?php echo esc_html($type->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Sort Filter -->
            <div class="hph-filter-dropdown">
                <select name="sort_by" class="hph-filter-select">
                    <option value="date_desc" <?php selected($sort_by, 'date_desc'); ?>><?php esc_html_e('Newest First', 'happy-place'); ?></option>
                    <option value="date_asc" <?php selected($sort_by, 'date_asc'); ?>><?php esc_html_e('Oldest First', 'happy-place'); ?></option>
                    <option value="price_desc" <?php selected($sort_by, 'price_desc'); ?>><?php esc_html_e('Price: High to Low', 'happy-place'); ?></option>
                    <option value="price_asc" <?php selected($sort_by, 'price_asc'); ?>><?php esc_html_e('Price: Low to High', 'happy-place'); ?></option>
                    <option value="title_asc" <?php selected($sort_by, 'title_asc'); ?>><?php esc_html_e('Title A-Z', 'happy-place'); ?></option>
                    <option value="views_desc" <?php selected($sort_by, 'views_desc'); ?>><?php esc_html_e('Most Viewed', 'happy-place'); ?></option>
                </select>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="hph-btn hph-btn--secondary">
                <i class="fas fa-filter"></i>
                <?php esc_html_e('Filter', 'happy-place'); ?>
            </button>
        </form>

        <!-- View Toggle -->
        <div class="hph-view-toggle">
            <button class="hph-view-btn hph-view-btn--active" data-view="grid" title="<?php esc_attr_e('Grid View', 'happy-place'); ?>">
                <i class="fas fa-th"></i>
            </button>
            <button class="hph-view-btn" data-view="list" title="<?php esc_attr_e('List View', 'happy-place'); ?>">
                <i class="fas fa-list"></i>
            </button>
            <button class="hph-view-btn" data-view="table" title="<?php esc_attr_e('Table View', 'happy-place'); ?>">
                <i class="fas fa-table"></i>
            </button>
        </div>
    </div>

    <!-- Listings Content -->
    <?php if ($listings_query->have_posts()) : ?>

        <!-- Grid View (Default) -->
        <div class="hph-listings-view hph-listings-view--grid" data-view="grid">
            <div class="hph-listings-grid">
                <?php while ($listings_query->have_posts()) : $listings_query->the_post();
                    $listing_id = get_the_ID();
                    $listing_price = get_field('listing_price', $listing_id);
                    $listing_address = get_field('listing_address', $listing_id);
                    $listing_images = get_field('listing_images', $listing_id);
                    $listing_status = get_field('listing_status', $listing_id) ?: 'active';
                    $listing_views = get_post_meta($listing_id, 'listing_views', true) ?: 0;
                    $listing_inquiries = get_post_meta($listing_id, 'listing_inquiries', true) ?: 0;
                    $bedrooms = get_field('bedrooms', $listing_id);
                    $bathrooms = get_field('bathrooms', $listing_id);
                    $sqft = get_field('square_footage', $listing_id);
                    
                    $featured_image = !empty($listing_images) ? $listing_images[0] : get_the_post_thumbnail_url($listing_id, 'medium');
                    
                    // Status classes
                    $status_classes = [
                        'active' => 'hph-listing-status--active',
                        'pending' => 'hph-listing-status--pending',
                        'sold' => 'hph-listing-status--sold',
                        'inactive' => 'hph-listing-status--inactive'
                    ];
                ?>
                    <div class="hph-listing-card" data-listing-id="<?php echo esc_attr($listing_id); ?>">
                        
                        <!-- Listing Image -->
                        <?php if ($featured_image) : ?>
                            <div class="hph-listing-image-wrapper">
                                <img src="<?php echo esc_url($featured_image); ?>" 
                                     alt="<?php echo esc_attr(get_the_title()); ?>" 
                                     class="hph-listing-image"
                                     loading="lazy">
                                
                                <!-- Status Badge -->
                                <div class="hph-listing-status-badge <?php echo esc_attr($status_classes[$listing_status] ?? 'hph-listing-status--active'); ?>">
                                    <?php echo esc_html(ucfirst($listing_status)); ?>
                                </div>
                                
                                <!-- Quick Actions Overlay -->
                                <div class="hph-listing-overlay-actions">
                                    <a href="<?php echo esc_url(get_permalink()); ?>" 
                                       class="hph-action-btn" 
                                       title="<?php esc_attr_e('View Listing', 'happy-place'); ?>"
                                       target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo esc_url(get_edit_post_link()); ?>" 
                                       class="hph-action-btn" 
                                       title="<?php esc_attr_e('Edit Listing', 'happy-place'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="hph-listing-image-placeholder">
                                <i class="fas fa-home"></i>
                                <span><?php esc_html_e('No Image', 'happy-place'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Listing Content -->
                        <div class="hph-listing-content">
                            
                            <!-- Price -->
                            <?php if ($listing_price) : ?>
                                <div class="hph-listing-price">
                                    $<?php echo esc_html(number_format($listing_price)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Title/Address -->
                            <h3 class="hph-listing-title">
                                <a href="<?php echo esc_url(get_edit_post_link()); ?>">
                                    <?php echo esc_html($listing_address ?: get_the_title()); ?>
                                </a>
                            </h3>
                            
                            <!-- Property Details -->
                            <?php if ($bedrooms || $bathrooms || $sqft) : ?>
                                <div class="hph-listing-details">
                                    <?php if ($bedrooms) : ?>
                                        <span><i class="fas fa-bed"></i> <?php echo esc_html($bedrooms); ?></span>
                                    <?php endif; ?>
                                    <?php if ($bathrooms) : ?>
                                        <span><i class="fas fa-bath"></i> <?php echo esc_html($bathrooms); ?></span>
                                    <?php endif; ?>
                                    <?php if ($sqft) : ?>
                                        <span><i class="fas fa-ruler-combined"></i> <?php echo esc_html(number_format($sqft)); ?> sq ft</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Performance Stats -->
                            <div class="hph-listing-stats">
                                <div class="hph-stat">
                                    <i class="fas fa-eye"></i>
                                    <span><?php echo esc_html(number_format($listing_views)); ?> <?php esc_html_e('views', 'happy-place'); ?></span>
                                </div>
                                <div class="hph-stat">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo esc_html($listing_inquiries); ?> <?php esc_html_e('inquiries', 'happy-place'); ?></span>
                                </div>
                                <div class="hph-stat">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp'))); ?> <?php esc_html_e('ago', 'happy-place'); ?></span>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="hph-listing-actions">
                                <a href="<?php echo esc_url(get_permalink()); ?>" 
                                   class="hph-btn hph-btn--outline hph-btn--sm"
                                   target="_blank">
                                    <i class="fas fa-external-link-alt"></i>
                                    <?php esc_html_e('View', 'happy-place'); ?>
                                </a>
                                
                                <div class="hph-dropdown">
                                    <button class="hph-btn hph-btn--secondary hph-btn--sm hph-dropdown-toggle">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <div class="hph-dropdown-menu">
                                        <a href="<?php echo esc_url(get_edit_post_link()); ?>" class="hph-dropdown-item">
                                            <i class="fas fa-edit"></i> <?php esc_html_e('Edit', 'happy-place'); ?>
                                        </a>
                                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'duplicate', 'listing_id' => $listing_id]), 'duplicate_listing_' . $listing_id)); ?>" class="hph-dropdown-item">
                                            <i class="fas fa-copy"></i> <?php esc_html_e('Duplicate', 'happy-place'); ?>
                                        </a>
                                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'toggle_status', 'listing_id' => $listing_id]), 'toggle_status_' . $listing_id)); ?>" class="hph-dropdown-item">
                                            <i class="fas fa-toggle-<?php echo $listing_status === 'active' ? 'off' : 'on'; ?>"></i> 
                                            <?php echo $listing_status === 'active' ? esc_html__('Deactivate', 'happy-place') : esc_html__('Activate', 'happy-place'); ?>
                                        </a>
                                        <div class="hph-dropdown-divider"></div>
                                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'delete', 'listing_id' => $listing_id]), 'delete_listing_' . $listing_id)); ?>" 
                                           class="hph-dropdown-item hph-dropdown-item--danger"
                                           onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this listing?', 'happy-place'); ?>')">
                                            <i class="fas fa-trash"></i> <?php esc_html_e('Delete', 'happy-place'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Table View -->
        <div class="hph-listings-view hph-listings-view--table hph-d-none" data-view="table">
            <div class="hph-table-wrapper">
                <table class="hph-listing-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Property', 'happy-place'); ?></th>
                            <th><?php esc_html_e('Price', 'happy-place'); ?></th>
                            <th><?php esc_html_e('Status', 'happy-place'); ?></th>
                            <th><?php esc_html_e('Details', 'happy-place'); ?></th>
                            <th><?php esc_html_e('Performance', 'happy-place'); ?></th>
                            <th><?php esc_html_e('Actions', 'happy-place'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset query for table view
                        $listings_query->rewind_posts();
                        while ($listings_query->have_posts()) : $listings_query->the_post();
                            $listing_id = get_the_ID();
                            $listing_price = get_field('listing_price', $listing_id);
                            $listing_address = get_field('listing_address', $listing_id);
                            $listing_status = get_field('listing_status', $listing_id) ?: 'active';
                            $listing_views = get_post_meta($listing_id, 'listing_views', true) ?: 0;
                            $listing_inquiries = get_post_meta($listing_id, 'listing_inquiries', true) ?: 0;
                            $bedrooms = get_field('bedrooms', $listing_id);
                            $bathrooms = get_field('bathrooms', $listing_id);
                            $sqft = get_field('square_footage', $listing_id);
                        ?>
                            <tr>
                                <td>
                                    <div class="hph-listing-address"><?php echo esc_html($listing_address ?: get_the_title()); ?></div>
                                    <div class="hph-listing-city"><?php echo esc_html(get_field('listing_city', $listing_id)); ?></div>
                                </td>
                                <td>
                                    <?php if ($listing_price) : ?>
                                        <div class="hph-listing-price">$<?php echo esc_html(number_format($listing_price)); ?></div>
                                    <?php else : ?>
                                        <span class="hph-text-gray-500">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="hph-listing-status hph-listing-status--<?php echo esc_attr($listing_status); ?>">
                                        <?php echo esc_html(ucfirst($listing_status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="hph-listing-details hph-listing-details--compact">
                                        <?php if ($bedrooms) : ?>
                                            <span><?php echo esc_html($bedrooms); ?>bd</span>
                                        <?php endif; ?>
                                        <?php if ($bathrooms) : ?>
                                            <span><?php echo esc_html($bathrooms); ?>ba</span>
                                        <?php endif; ?>
                                        <?php if ($sqft) : ?>
                                            <span><?php echo esc_html(number_format($sqft)); ?> sq ft</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="hph-performance-mini">
                                        <div><?php echo esc_html($listing_views); ?> views</div>
                                        <div><?php echo esc_html($listing_inquiries); ?> inquiries</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="hph-listing-actions">
                                        <a href="<?php echo esc_url(get_permalink()); ?>" 
                                           class="hph-listing-action" 
                                           title="<?php esc_attr_e('View Listing', 'happy-place'); ?>"
                                           target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo esc_url(get_edit_post_link()); ?>" 
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
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($listings_query->max_num_pages > 1) : ?>
            <div class="hph-pagination">
                <?php
                echo paginate_links([
                    'total' => $listings_query->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'format' => '?paged=%#%',
                    'show_all' => false,
                    'end_size' => 1,
                    'mid_size' => 2,
                    'prev_next' => true,
                    'prev_text' => '<i class="fas fa-chevron-left"></i> ' . __('Previous', 'happy-place'),
                    'next_text' => __('Next', 'happy-place') . ' <i class="fas fa-chevron-right"></i>',
                    'add_args' => array_filter([
                        'section' => 'listings',
                        'search' => $search,
                        'status' => $status_filter,
                        'property_type' => $property_type,
                        'sort_by' => $sort_by
                    ])
                ]);
                ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        
        <!-- Empty State -->
        <div class="hph-empty-state">
            <div class="hph-empty-state-icon">
                <i class="fas fa-home"></i>
            </div>
            <h3 class="hph-empty-state-title">
                <?php echo $search || $status_filter || $property_type ? 
                    esc_html__('No listings found', 'happy-place') : 
                    esc_html__('No listings yet', 'happy-place'); ?>
            </h3>
            <p class="hph-empty-state-description">
                <?php if ($search || $status_filter || $property_type) : ?>
                    <?php esc_html_e('Try adjusting your search criteria or filters to find what you\'re looking for.', 'happy-place'); ?>
                <?php else : ?>
                    <?php esc_html_e('Start building your portfolio by adding your first property listing.', 'happy-place'); ?>
                <?php endif; ?>
            </p>
            <div class="hph-empty-state-actions">
                <?php if ($search || $status_filter || $property_type) : ?>
                    <a href="<?php echo esc_url(add_query_arg('section', 'listings')); ?>" class="hph-btn hph-btn--secondary">
                        <i class="fas fa-times"></i>
                        <?php esc_html_e('Clear Filters', 'happy-place'); ?>
                    </a>
                <?php endif; ?>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=listing')); ?>" class="hph-btn hph-btn--primary">
                    <i class="fas fa-plus"></i>
                    <?php esc_html_e('Add Your First Listing', 'happy-place'); ?>
                </a>
            </div>
        </div>

    <?php endif; ?>

    <?php wp_reset_postdata(); ?>

</div>

<style>
/* Listings Section Specific Styles */
.hph-listings-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--hph-spacing-6);
    padding: var(--hph-spacing-4) var(--hph-spacing-6);
    background: var(--hph-color-white);
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-xl);
    box-shadow: var(--hph-shadow-sm);
    flex-wrap: wrap;
    gap: var(--hph-spacing-4);
}

.hph-search-wrapper {
    position: relative;
    min-width: 300px;
}

.hph-search-input {
    width: 100%;
    padding: var(--hph-spacing-3) var(--hph-spacing-4) var(--hph-spacing-3) var(--hph-spacing-10);
    border: 1px solid var(--hph-color-gray-300);
    border-radius: var(--hph-radius-lg);
    font-size: var(--hph-font-size-sm);
    background: var(--hph-color-white);
    transition: all var(--hph-transition-base);
}

.hph-search-input:focus {
    outline: none;
    border-color: var(--hph-color-primary-400);
    box-shadow: 0 0 0 3px var(--hph-color-primary-100);
}

.hph-search-icon {
    position: absolute;
    left: var(--hph-spacing-3);
    top: 50%;
    transform: translateY(-50%);
    color: var(--hph-color-gray-400);
    pointer-events: none;
}

.hph-filter-select {
    padding: var(--hph-spacing-2) var(--hph-spacing-3);
    border: 1px solid var(--hph-color-gray-300);
    border-radius: var(--hph-radius-lg);
    font-size: var(--hph-font-size-sm);
    background: var(--hph-color-white);
    min-width: 140px;
}

.hph-listing-image-wrapper {
    position: relative;
    overflow: hidden;
    border-radius: var(--hph-radius-lg) var(--hph-radius-lg) 0 0;
    height: 200px;
}

.hph-listing-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--hph-transition-base);
}

.hph-listing-card:hover .hph-listing-image {
    transform: scale(1.05);
}

.hph-listing-image-placeholder {
    height: 200px;
    background: var(--hph-color-gray-100);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--hph-color-gray-400);
    border-radius: var(--hph-radius-lg) var(--hph-radius-lg) 0 0;
}

.hph-listing-image-placeholder i {
    font-size: var(--hph-font-size-2xl);
    margin-bottom: var(--hph-spacing-2);
}

.hph-listing-overlay-actions {
    position: absolute;
    top: var(--hph-spacing-3);
    right: var(--hph-spacing-3);
    display: flex;
    gap: var(--hph-spacing-2);
    opacity: 0;
    transition: opacity var(--hph-transition-base);
}

.hph-listing-card:hover .hph-listing-overlay-actions {
    opacity: 1;
}

.hph-action-btn {
    width: 36px;
    height: 36px;
    background: rgba(0, 0, 0, 0.7);
    color: var(--hph-color-white);
    border-radius: var(--hph-radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    backdrop-filter: blur(10px);
    transition: all var(--hph-transition-fast);
}

.hph-action-btn:hover {
    background: var(--hph-color-primary-600);
    transform: scale(1.1);
}

.hph-listing-title {
    margin: 0 0 var(--hph-spacing-2);
    font-size: var(--hph-font-size-base);
    font-weight: var(--hph-font-semibold);
    line-height: 1.4;
}

.hph-listing-title a {
    color: var(--hph-color-gray-900);
    text-decoration: none;
    transition: color var(--hph-transition-fast);
}

.hph-listing-title a:hover {
    color: var(--hph-color-primary-600);
}

.hph-listing-stats {
    display: flex;
    gap: var(--hph-spacing-4);
    margin: var(--hph-spacing-3) 0;
    padding: var(--hph-spacing-3) 0;
    border-top: 1px solid var(--hph-color-gray-100);
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-500);
}

.hph-stat {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-1);
}

.hph-stat i {
    color: var(--hph-color-primary-500);
}

.hph-listing-details--compact {
    display: flex;
    gap: var(--hph-spacing-2);
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
}

.hph-performance-mini {
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-600);
    line-height: 1.4;
}

.hph-dropdown {
    position: relative;
    display: inline-block;
}

.hph-dropdown-toggle::after {
    content: none;
}

.hph-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    min-width: 180px;
    background: var(--hph-color-white);
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-lg);
    box-shadow: var(--hph-shadow-lg);
    z-index: var(--hph-z-dropdown);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all var(--hph-transition-base);
    overflow: hidden;
    margin-top: var(--hph-spacing-1);
}

.hph-dropdown:hover .hph-dropdown-menu,
.hph-dropdown.hph-dropdown--active .hph-dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.hph-dropdown-item {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-2);
    padding: var(--hph-spacing-2) var(--hph-spacing-3);
    color: var(--hph-color-gray-700);
    text-decoration: none;
    font-size: var(--hph-font-size-sm);
    transition: all var(--hph-transition-fast);
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.hph-dropdown-item:hover {
    background: var(--hph-color-gray-50);
    color: var(--hph-color-gray-900);
}

.hph-dropdown-item--danger {
    color: var(--hph-color-danger);
}

.hph-dropdown-item--danger:hover {
    background: var(--hph-color-danger-light);
    color: var(--hph-color-danger-dark);
}

.hph-dropdown-divider {
    height: 1px;
    background: var(--hph-color-gray-100);
    margin: var(--hph-spacing-1) 0;
}

.hph-section-header {
    margin-bottom: var(--hph-spacing-6);
}

.hph-section-actions {
    display: flex;
    gap: var(--hph-spacing-3);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hph-listings-toolbar {
        flex-direction: column;
        align-items: stretch;
        gap: var(--hph-spacing-3);
    }
    
    .hph-listings-search {
        flex-direction: column;
        gap: var(--hph-spacing-2);
    }
    
    .hph-search-wrapper {
        min-width: auto;
    }
    
    .hph-filter-select {
        min-width: auto;
    }
    
    .hph-section-header {
        flex-direction: column;
        gap: var(--hph-spacing-3);
        align-items: flex-start;
    }
    
    .hph-listings-grid {
        grid-template-columns: 1fr;
    }
    
    .hph-table-wrapper {
        overflow-x: auto;
    }
    
    .hph-listing-table {
        min-width: 800px;
    }
}

@media (max-width: 480px) {
    .hph-listing-stats {
        flex-direction: column;
        gap: var(--hph-spacing-2);
    }
    
    .hph-listing-actions {
        flex-direction: column;
        gap: var(--hph-spacing-2);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View toggle functionality
    const viewButtons = document.querySelectorAll('.hph-view-btn');
    const viewContainers = document.querySelectorAll('.hph-listings-view');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetView = this.dataset.view;
            
            // Update active button
            viewButtons.forEach(btn => btn.classList.remove('hph-view-btn--active'));
            this.classList.add('hph-view-btn--active');
            
            // Show/hide appropriate view
            viewContainers.forEach(container => {
                if (container.dataset.view === targetView) {
                    container.classList.remove('hph-d-none');
                } else {
                    container.classList.add('hph-d-none');
                }
            });
            
            // Store preference
            localStorage.setItem('hph_listings_view', targetView);
        });
    });
    
    // Restore saved view preference
    const savedView = localStorage.getItem('hph_listings_view');
    if (savedView) {
        const targetButton = document.querySelector(`[data-view="${savedView}"]`);
        if (targetButton) {
            targetButton.click();
        }
    }
    
    // Dropdown functionality
    const dropdowns = document.querySelectorAll('.hph-dropdown');
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.hph-dropdown-toggle');
        const menu = dropdown.querySelector('.hph-dropdown-menu');
        
        if (toggle && menu) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close other dropdowns
                dropdowns.forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.classList.remove('hph-dropdown--active');
                    }
                });
                
                // Toggle current dropdown
                dropdown.classList.toggle('hph-dropdown--active');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('hph-dropdown--active');
        });
    });
    
    // Analytics modal functionality
    const analyticsButtons = document.querySelectorAll('.hph-listing-action--analytics');
    analyticsButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const listingId = this.dataset.listingId;
            // Here you would open an analytics modal or navigate to analytics page
            console.log('Show analytics for listing:', listingId);
            
            // Example: Show toast notification
            if (window.HphDashboard && window.HphDashboard.showToast) {
                window.HphDashboard.showToast('Analytics feature coming soon!', 'info');
            }
        });
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
 * Helper function to duplicate a listing
 */
function hph_duplicate_listing($listing_id) {
    $original_post = get_post($listing_id);
    if (!$original_post) {
        return false;
    }
    
    // Create new post array
    $new_post = array(
        'post_title'     => $original_post->post_title . ' (Copy)',
        'post_content'   => $original_post->post_content,
        'post_status'    => 'draft',
        'post_author'    => $original_post->post_author,
        'post_type'      => $original_post->post_type,
        'comment_status' => $original_post->comment_status,
        'ping_status'    => $original_post->ping_status,
    );
    
    // Insert the new post
    $new_listing_id = wp_insert_post($new_post);
    
    if ($new_listing_id && !is_wp_error($new_listing_id)) {
        // Copy all custom fields
        $custom_fields = get_post_meta($listing_id);
        foreach ($custom_fields as $key => $values) {
            foreach ($values as $value) {
                add_post_meta($new_listing_id, $key, maybe_unserialize($value));
            }
        }
        
        // Copy taxonomies
        $taxonomies = get_object_taxonomies($original_post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($listing_id, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($new_listing_id, $terms, $taxonomy);
        }
        
        return $new_listing_id;
    }
    
    return false;
}
?>