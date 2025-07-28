<?php
/**
 * Dashboard Listings Section Template
 * 
 * Displays and manages agent listings with form integration and flyer generator
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

// Get current action from query params
$current_action = sanitize_text_field($_GET['action'] ?? '');
$listing_id = intval($_GET['listing_id'] ?? 0);

// Handle specific actions (new listing, edit listing)
if (in_array($current_action, ['new', 'edit'])) {
    // Load the advanced listing form
    $form_template = locate_template('templates/template-parts/dashboard/form-listing-advanced.php');
    if ($form_template) {
        include $form_template;
        return; // Exit early to show only the form
    }
}

// Default listings view
// Get listings query parameters
$search = sanitize_text_field($_GET['search'] ?? '');
$status_filter = sanitize_text_field($_GET['status'] ?? '');
$sort_by = sanitize_text_field($_GET['sort'] ?? 'date_desc');
$per_page = 12;
$paged = max(1, intval($_GET['paged'] ?? 1));

// Build query arguments
$query_args = [
    'post_type' => 'listing',
    'author' => $current_agent_id,
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'post_status' => ['publish', 'draft', 'pending']
];

// Add search
if (!empty($search)) {
    $query_args['s'] = $search;
}

// Add status filter
if (!empty($status_filter)) {
    $query_args['post_status'] = [$status_filter];
}

// Add sorting
switch ($sort_by) {
    case 'title_asc':
        $query_args['orderby'] = 'title';
        $query_args['order'] = 'ASC';
        break;
    case 'title_desc':
        $query_args['orderby'] = 'title';
        $query_args['order'] = 'DESC';
        break;
    case 'price_asc':
        $query_args['meta_key'] = 'price';
        $query_args['orderby'] = 'meta_value_num';
        $query_args['order'] = 'ASC';
        break;
    case 'price_desc':
        $query_args['meta_key'] = 'price';
        $query_args['orderby'] = 'meta_value_num';
        $query_args['order'] = 'DESC';
        break;
    case 'date_asc':
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'ASC';
        break;
    default: // date_desc
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
        break;
}

// Execute query
$listings_query = new WP_Query($query_args);

// Get total counts for stats
$all_listings = get_posts([
    'author' => $current_agent_id,
    'post_type' => 'listing',
    'numberposts' => -1,
    'post_status' => ['publish', 'draft', 'pending'],
    'fields' => 'ids'
]);

$active_count = count(get_posts([
    'author' => $current_agent_id,
    'post_type' => 'listing',
    'numberposts' => -1,
    'post_status' => 'publish',
    'fields' => 'ids'
]));

$draft_count = count(get_posts([
    'author' => $current_agent_id,
    'post_type' => 'listing',
    'numberposts' => -1,
    'post_status' => 'draft',
    'fields' => 'ids'
]));

$pending_count = count(get_posts([
    'author' => $current_agent_id,
    'post_type' => 'listing',
    'numberposts' => -1,
    'post_status' => 'pending',
    'fields' => 'ids'
]));
?>

<div class="hph-dashboard-listings">
    
    <!-- Listings Header -->
    <div class="hph-listings-header">
        <div class="hph-listings-title-group">
            <h2><?php esc_html_e('My Listings', 'happy-place'); ?></h2>
            <p class="hph-listings-description">
                <?php esc_html_e('Manage your property listings, create marketing materials, and track performance.', 'happy-place'); ?>
            </p>
        </div>
        <div class="hph-listings-actions">
            <a href="<?php echo esc_url(add_query_arg(['section' => 'listings', 'action' => 'new'])); ?>" 
               class="hph-btn hph-btn--primary">
                <i class="fas fa-plus"></i>
                <?php esc_html_e('Add New Listing', 'happy-place'); ?>
            </a>
        </div>
    </div>

    <!-- Listings Stats -->
    <div class="hph-listings-stats">
        <div class="hph-stat-item <?php echo empty($status_filter) ? 'hph-stat-item--active' : ''; ?>">
            <a href="<?php echo esc_url(remove_query_arg('status')); ?>">
                <div class="hph-stat-number"><?php echo count($all_listings); ?></div>
                <div class="hph-stat-label"><?php esc_html_e('Total', 'happy-place'); ?></div>
            </a>
        </div>
        <div class="hph-stat-item <?php echo $status_filter === 'publish' ? 'hph-stat-item--active' : ''; ?>">
            <a href="<?php echo esc_url(add_query_arg('status', 'publish')); ?>">
                <div class="hph-stat-number"><?php echo $active_count; ?></div>
                <div class="hph-stat-label"><?php esc_html_e('Active', 'happy-place'); ?></div>
            </a>
        </div>
        <div class="hph-stat-item <?php echo $status_filter === 'draft' ? 'hph-stat-item--active' : ''; ?>">
            <a href="<?php echo esc_url(add_query_arg('status', 'draft')); ?>">
                <div class="hph-stat-number"><?php echo $draft_count; ?></div>
                <div class="hph-stat-label"><?php esc_html_e('Drafts', 'happy-place'); ?></div>
            </a>
        </div>
        <div class="hph-stat-item <?php echo $status_filter === 'pending' ? 'hph-stat-item--active' : ''; ?>">
            <a href="<?php echo esc_url(add_query_arg('status', 'pending')); ?>">
                <div class="hph-stat-number"><?php echo $pending_count; ?></div>
                <div class="hph-stat-label"><?php esc_html_e('Pending', 'happy-place'); ?></div>
            </a>
        </div>
    </div>

    <!-- Listings Controls -->
    <div class="hph-listings-controls">
        <form method="GET" class="hph-listings-filter-form">
            <input type="hidden" name="section" value="listings">
            <?php if (!empty($status_filter)) : ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
            <?php endif; ?>
            
            <div class="hph-filter-group">
                <div class="hph-search-field">
                    <i class="fas fa-search"></i>
                    <input type="search" 
                           name="search" 
                           value="<?php echo esc_attr($search); ?>"
                           placeholder="<?php esc_attr_e('Search listings...', 'happy-place'); ?>"
                           class="hph-search-input">
                </div>
                
                <select name="sort" class="hph-sort-select">
                    <option value="date_desc" <?php selected($sort_by, 'date_desc'); ?>><?php esc_html_e('Newest First', 'happy-place'); ?></option>
                    <option value="date_asc" <?php selected($sort_by, 'date_asc'); ?>><?php esc_html_e('Oldest First', 'happy-place'); ?></option>
                    <option value="title_asc" <?php selected($sort_by, 'title_asc'); ?>><?php esc_html_e('Title A-Z', 'happy-place'); ?></option>
                    <option value="title_desc" <?php selected($sort_by, 'title_desc'); ?>><?php esc_html_e('Title Z-A', 'happy-place'); ?></option>
                    <option value="price_desc" <?php selected($sort_by, 'price_desc'); ?>><?php esc_html_e('Price: High to Low', 'happy-place'); ?></option>
                    <option value="price_asc" <?php selected($sort_by, 'price_asc'); ?>><?php esc_html_e('Price: Low to High', 'happy-place'); ?></option>
                </select>
                
                <button type="submit" class="hph-btn hph-btn--outline">
                    <i class="fas fa-filter"></i>
                    <?php esc_html_e('Filter', 'happy-place'); ?>
                </button>
            </div>
        </form>

        <div class="hph-view-toggle">
            <button type="button" class="hph-view-btn hph-view-btn--active" data-view="grid" title="<?php esc_attr_e('Grid View', 'happy-place'); ?>">
                <i class="fas fa-th"></i>
            </button>
            <button type="button" class="hph-view-btn" data-view="list" title="<?php esc_attr_e('List View', 'happy-place'); ?>">
                <i class="fas fa-list"></i>
            </button>
        </div>
    </div>

    <!-- Listings Content -->
    <div class="hph-listings-content">
        
        <?php if ($listings_query->have_posts()) : ?>
            
            <!-- Listings Grid -->
            <div class="hph-listings-grid" id="listings-grid">
                
                <?php while ($listings_query->have_posts()) : $listings_query->the_post(); ?>
                    <?php
                    $listing_id = get_the_ID();
                    $price = get_field('price', $listing_id);
                    $status = get_field('status', $listing_id) ?: get_post_status();
                    $property_type = get_field('property_type', $listing_id);
                    $bedrooms = get_field('bedrooms', $listing_id);
                    $bathrooms = get_field('bathrooms', $listing_id);
                    $square_footage = get_field('square_footage', $listing_id);
                    $mls_number = get_field('mls_number', $listing_id);
                    $list_date = get_field('list_date', $listing_id);
                    $views_count = get_post_meta($listing_id, '_views_count', true) ?: 0;
                    ?>
                    
                    <div class="hph-listing-card" data-listing-id="<?php echo $listing_id; ?>">
                        
                        <!-- Listing Image -->
                        <div class="hph-listing-card-image">
                            <?php if (has_post_thumbnail()) : ?>
                                <a href="<?php echo esc_url(get_permalink()); ?>" target="_blank">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                            <?php else : ?>
                                <div class="hph-listing-placeholder">
                                    <i class="fas fa-home"></i>
                                    <span><?php esc_html_e('No Image', 'happy-place'); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Status Badge -->
                            <div class="hph-listing-status-badge hph-listing-status-badge--<?php echo esc_attr($status); ?>">
                                <?php echo esc_html(ucfirst($status)); ?>
                            </div>
                            
                            <!-- Quick Actions Overlay -->
                            <div class="hph-listing-card-overlay">
                                <div class="hph-listing-quick-actions">
                                    <button type="button" 
                                            class="hph-quick-action-btn hph-quick-action-btn--view"
                                            onclick="window.open('<?php echo esc_url(get_permalink()); ?>', '_blank')"
                                            title="<?php esc_attr_e('View Listing', 'happy-place'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" 
                                            class="hph-quick-action-btn hph-quick-action-btn--edit"
                                            onclick="editListing(<?php echo $listing_id; ?>)"
                                            title="<?php esc_attr_e('Edit Listing', 'happy-place'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" 
                                            class="hph-quick-action-btn hph-quick-action-btn--flyer"
                                            onclick="generateFlyer(<?php echo $listing_id; ?>)"
                                            title="<?php esc_attr_e('Generate Flyer', 'happy-place'); ?>">
                                        <i class="fas fa-palette"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Listing Content -->
                        <div class="hph-listing-card-content">
                            <div class="hph-listing-card-header">
                                <h3 class="hph-listing-card-title">
                                    <a href="<?php echo esc_url(get_permalink()); ?>" target="_blank">
                                        <?php the_title(); ?>
                                    </a>
                                </h3>
                                <?php if ($price) : ?>
                                    <div class="hph-listing-card-price">
                                        $<?php echo number_format($price); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Property Details -->
                            <div class="hph-listing-card-details">
                                <?php if ($property_type) : ?>
                                    <span class="hph-listing-detail">
                                        <i class="fas fa-building"></i>
                                        <?php echo esc_html(ucfirst($property_type)); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($bedrooms) : ?>
                                    <span class="hph-listing-detail">
                                        <i class="fas fa-bed"></i>
                                        <?php echo $bedrooms; ?> bed
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($bathrooms) : ?>
                                    <span class="hph-listing-detail">
                                        <i class="fas fa-bath"></i>
                                        <?php echo $bathrooms; ?> bath
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($square_footage) : ?>
                                    <span class="hph-listing-detail">
                                        <i class="fas fa-ruler-combined"></i>
                                        <?php echo number_format($square_footage); ?> sq ft
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Listing Meta -->
                            <div class="hph-listing-card-meta">
                                <?php if ($mls_number) : ?>
                                    <span class="hph-listing-meta">
                                        <strong><?php esc_html_e('MLS:', 'happy-place'); ?></strong> 
                                        <?php echo esc_html($mls_number); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($list_date) : ?>
                                    <span class="hph-listing-meta">
                                        <strong><?php esc_html_e('Listed:', 'happy-place'); ?></strong> 
                                        <?php echo date('M j, Y', strtotime($list_date)); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <span class="hph-listing-meta">
                                    <strong><?php esc_html_e('Views:', 'happy-place'); ?></strong> 
                                    <?php echo number_format($views_count); ?>
                                </span>
                            </div>
                            
                            <!-- Actions -->
                            <div class="hph-listing-card-actions">
                                <a href="<?php echo esc_url(add_query_arg(['section' => 'listings', 'action' => 'edit', 'listing_id' => $listing_id])); ?>" 
                                   class="hph-btn hph-btn--sm hph-btn--outline">
                                    <i class="fas fa-edit"></i>
                                    <?php esc_html_e('Edit', 'happy-place'); ?>
                                </a>
                                
                                <button type="button" 
                                        class="hph-btn hph-btn--sm hph-btn--primary"
                                        onclick="generateFlyer(<?php echo $listing_id; ?>)">
                                    <i class="fas fa-palette"></i>
                                    <?php esc_html_e('Create Flyer', 'happy-place'); ?>
                                </button>
                                
                                <div class="hph-listing-actions-dropdown">
                                    <button type="button" class="hph-btn hph-btn--sm hph-btn--outline hph-dropdown-toggle">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <div class="hph-dropdown-menu">
                                        <a href="<?php echo esc_url(get_permalink()); ?>" target="_blank" class="hph-dropdown-item">
                                            <i class="fas fa-eye"></i>
                                            <?php esc_html_e('View Live', 'happy-place'); ?>
                                        </a>
                                        <button type="button" class="hph-dropdown-item" onclick="duplicateListing(<?php echo $listing_id; ?>)">
                                            <i class="fas fa-copy"></i>
                                            <?php esc_html_e('Duplicate', 'happy-place'); ?>
                                        </button>
                                        <button type="button" class="hph-dropdown-item hph-dropdown-item--danger" onclick="deleteListing(<?php echo $listing_id; ?>)">
                                            <i class="fas fa-trash"></i>
                                            <?php esc_html_e('Delete', 'happy-place'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php endwhile; ?>
                
            </div>
            
            <!-- Pagination -->
            <?php if ($listings_query->max_num_pages > 1) : ?>
                <div class="hph-pagination">
                    <?php
                    $pagination_args = [
                        'total' => $listings_query->max_num_pages,
                        'current' => $paged,
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '<i class="fas fa-chevron-left"></i> ' . __('Previous', 'happy-place'),
                        'next_text' => __('Next', 'happy-place') . ' <i class="fas fa-chevron-right"></i>',
                        'type' => 'list',
                        'end_size' => 3,
                        'mid_size' => 3,
                        'add_args' => array_filter([
                            'section' => 'listings',
                            'search' => $search,
                            'status' => $status_filter,
                            'sort' => $sort_by
                        ])
                    ];
                    echo paginate_links($pagination_args);
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
                    <?php
                    if (!empty($search) || !empty($status_filter)) {
                        esc_html_e('No listings found', 'happy-place');
                    } else {
                        esc_html_e('No listings yet', 'happy-place');
                    }
                    ?>
                </h3>
                <p class="hph-empty-state-description">
                    <?php
                    if (!empty($search) || !empty($status_filter)) {
                        esc_html_e('Try adjusting your search criteria or filters.', 'happy-place');
                    } else {
                        esc_html_e('Create your first listing to get started with your real estate business.', 'happy-place');
                    }
                    ?>
                </p>
                <div class="hph-empty-state-actions">
                    <?php if (!empty($search) || !empty($status_filter)) : ?>
                        <a href="<?php echo esc_url(remove_query_arg(['search', 'status', 'sort', 'paged'])); ?>" 
                           class="hph-btn hph-btn--outline">
                            <i class="fas fa-times"></i>
                            <?php esc_html_e('Clear Filters', 'happy-place'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(add_query_arg(['section' => 'listings', 'action' => 'new'])); ?>" 
                       class="hph-btn hph-btn--primary">
                        <i class="fas fa-plus"></i>
                        <?php esc_html_e('Add New Listing', 'happy-place'); ?>
                    </a>
                </div>
            </div>
            
        <?php endif; ?>
        
        <?php wp_reset_postdata(); ?>
        
    </div>
</div>

<script>
// Listings section JavaScript
function editListing(listingId) {
    window.location.href = '<?php echo esc_url(add_query_arg(['section' => 'listings', 'action' => 'edit'])); ?>&listing_id=' + listingId;
}

function generateFlyer(listingId) {
    // Redirect to marketing section with the selected listing for flyer generation
    window.location.href = '<?php echo esc_url(add_query_arg('section', 'marketing')); ?>&listing_id=' + listingId;
}

function duplicateListing(listingId) {
    if (!confirm('<?php esc_html_e('Are you sure you want to duplicate this listing?', 'happy-place'); ?>')) {
        return;
    }
    
    // AJAX call to duplicate listing
    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'hph_duplicate_listing',
        listing_id: listingId,
        nonce: '<?php echo wp_create_nonce('hph_ajax_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.data.message || 'Error duplicating listing.');
        }
    });
}

function deleteListing(listingId) {
    if (!confirm('<?php esc_html_e('Are you sure you want to delete this listing? This action cannot be undone.', 'happy-place'); ?>')) {
        return;
    }
    
    // AJAX call to delete listing
    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'hph_delete_listing',
        listing_id: listingId,
        nonce: '<?php echo wp_create_nonce('hph_ajax_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert(response.data.message || 'Error deleting listing.');
        }
    });
}

// Initialize listings features
jQuery(document).ready(function($) {
    // View toggle functionality
    $('.hph-view-btn').on('click', function() {
        const view = $(this).data('view');
        $('.hph-view-btn').removeClass('hph-view-btn--active');
        $(this).addClass('hph-view-btn--active');
        
        if (view === 'list') {
            $('#listings-grid').addClass('hph-listings-grid--list');
        } else {
            $('#listings-grid').removeClass('hph-listings-grid--list');
        }
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
    
    // Auto-submit filter form on select change
    $('.hph-sort-select').on('change', function() {
        $(this).closest('form').submit();
    });
});
</script>

<style>
/* Listings Section Specific Styles */
.hph-dashboard-listings {
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-6);
}

.hph-listings-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--hph-spacing-4);
}

.hph-listings-title-group h2 {
    margin: 0 0 var(--hph-spacing-2) 0;
    font-size: var(--hph-font-size-2xl);
    font-weight: var(--hph-font-bold);
}

.hph-listings-description {
    margin: 0;
    color: var(--hph-color-gray-600);
}

.hph-listings-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--hph-spacing-4);
}

.hph-stat-item {
    background: var(--hph-color-white);
    border-radius: var(--hph-radius-lg);
    border: 1px solid var(--hph-color-gray-200);
    transition: all 0.2s ease;
}

.hph-stat-item:hover {
    border-color: var(--hph-color-primary-300);
    box-shadow: var(--hph-shadow-sm);
}

.hph-stat-item--active {
    border-color: var(--hph-color-primary-500);
    background: var(--hph-color-primary-25);
}

.hph-stat-item a {
    display: block;
    padding: var(--hph-spacing-4);
    text-align: center;
    text-decoration: none;
    color: inherit;
}

.hph-stat-number {
    font-size: var(--hph-font-size-xl);
    font-weight: var(--hph-font-bold);
    color: var(--hph-color-gray-900);
    margin-bottom: var(--hph-spacing-1);
}

.hph-stat-label {
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hph-listings-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--hph-spacing-4);
    padding: var(--hph-spacing-4);
    background: var(--hph-color-white);
    border-radius: var(--hph-radius-lg);
    border: 1px solid var(--hph-color-gray-200);
}

.hph-filter-group {
    display: flex;
    gap: var(--hph-spacing-3);
    align-items: center;
}

.hph-search-field {
    position: relative;
    display: flex;
    align-items: center;
}

.hph-search-field i {
    position: absolute;
    left: var(--hph-spacing-3);
    color: var(--hph-color-gray-400);
    z-index: 1;
}

.hph-search-input {
    padding-left: var(--hph-spacing-8);
    min-width: 250px;
}

.hph-sort-select {
    min-width: 180px;
}

.hph-view-toggle {
    display: flex;
    gap: var(--hph-spacing-1);
    padding: var(--hph-spacing-1);
    background: var(--hph-color-gray-100);
    border-radius: var(--hph-radius-md);
}

.hph-view-btn {
    padding: var(--hph-spacing-2);
    background: transparent;
    border: none;
    border-radius: var(--hph-radius-sm);
    color: var(--hph-color-gray-600);
    cursor: pointer;
    transition: all 0.2s ease;
}

.hph-view-btn:hover {
    color: var(--hph-color-gray-900);
}

.hph-view-btn--active {
    background: var(--hph-color-white);
    color: var(--hph-color-primary-600);
    box-shadow: var(--hph-shadow-sm);
}

.hph-listings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--hph-spacing-5);
}

.hph-listings-grid--list {
    grid-template-columns: 1fr;
}

.hph-listings-grid--list .hph-listing-card {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: var(--hph-spacing-4);
}

.hph-listing-card {
    background: var(--hph-color-white);
    border-radius: var(--hph-radius-xl);
    border: 1px solid var(--hph-color-gray-200);
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.hph-listing-card:hover {
    border-color: var(--hph-color-primary-300);
    box-shadow: var(--hph-shadow-lg);
    transform: translateY(-2px);
}

.hph-listing-card-image {
    position: relative;
    aspect-ratio: 16/10;
    overflow: hidden;
}

.hph-listing-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.hph-listing-card:hover .hph-listing-card-image img {
    transform: scale(1.05);
}

.hph-listing-placeholder {
    width: 100%;
    height: 100%;
    background: var(--hph-color-gray-100);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--hph-color-gray-400);
    gap: var(--hph-spacing-2);
}

.hph-listing-placeholder i {
    font-size: 2rem;
}

.hph-listing-status-badge {
    position: absolute;
    top: var(--hph-spacing-3);
    left: var(--hph-spacing-3);
    padding: var(--hph-spacing-1) var(--hph-spacing-2);
    border-radius: var(--hph-radius-md);
    font-size: var(--hph-font-size-xs);
    font-weight: var(--hph-font-medium);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hph-listing-status-badge--publish {
    background: var(--hph-color-success-500);
    color: white;
}

.hph-listing-status-badge--draft {
    background: var(--hph-color-warning-500);
    color: white;
}

.hph-listing-status-badge--pending {
    background: var(--hph-color-primary-500);
    color: white;
}

.hph-listing-card-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.hph-listing-card:hover .hph-listing-card-overlay {
    opacity: 1;
}

.hph-listing-quick-actions {
    display: flex;
    gap: var(--hph-spacing-2);
}

.hph-quick-action-btn {
    width: 40px;
    height: 40px;
    border-radius: var(--hph-radius-md);
    border: none;
    background: rgba(255, 255, 255, 0.9);
    color: var(--hph-color-gray-700);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hph-quick-action-btn:hover {
    background: white;
    transform: scale(1.1);
}

.hph-quick-action-btn--edit:hover {
    color: var(--hph-color-primary-600);
}

.hph-quick-action-btn--flyer:hover {
    color: var(--hph-color-purple-600);
}

.hph-quick-action-btn--view:hover {
    color: var(--hph-color-success-600);
}

.hph-listing-card-content {
    padding: var(--hph-spacing-5);
    display: flex;
    flex-direction: column;
    gap: var(--hph-spacing-4);
}

.hph-listing-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--hph-spacing-3);
}

.hph-listing-card-title {
    margin: 0;
    font-size: var(--hph-font-size-lg);
    font-weight: var(--hph-font-semibold);
    flex: 1;
}

.hph-listing-card-title a {
    color: inherit;
    text-decoration: none;
    transition: color 0.2s ease;
}

.hph-listing-card-title a:hover {
    color: var(--hph-color-primary-600);
}

.hph-listing-card-price {
    font-size: var(--hph-font-size-lg);
    font-weight: var(--hph-font-bold);
    color: var(--hph-color-primary-600);
}

.hph-listing-card-details {
    display: flex;
    flex-wrap: wrap;
    gap: var(--hph-spacing-3);
}

.hph-listing-detail {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-1);
    font-size: var(--hph-font-size-sm);
    color: var(--hph-color-gray-600);
}

.hph-listing-detail i {
    color: var(--hph-color-gray-400);
}

.hph-listing-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--hph-spacing-3);
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-500);
}

.hph-listing-card-actions {
    display: flex;
    gap: var(--hph-spacing-2);
    align-items: center;
    margin-top: auto;
}

.hph-listing-actions-dropdown {
    position: relative;
    margin-left: auto;
}

.hph-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--hph-color-white);
    border: 1px solid var(--hph-color-gray-200);
    border-radius: var(--hph-radius-lg);
    box-shadow: var(--hph-shadow-lg);
    min-width: 150px;
    z-index: 100;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
}

.hph-dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.hph-dropdown-item {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-2);
    padding: var(--hph-spacing-2) var(--hph-spacing-3);
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    color: var(--hph-color-gray-700);
    cursor: pointer;
    transition: background-color 0.2s ease;
    font-size: var(--hph-font-size-sm);
}

.hph-dropdown-item:hover {
    background: var(--hph-color-gray-50);
}

.hph-dropdown-item--danger {
    color: var(--hph-color-danger-600);
}

.hph-dropdown-item--danger:hover {
    background: var(--hph-color-danger-50);
}

@media (max-width: 768px) {
    .hph-listings-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .hph-listings-controls {
        flex-direction: column;
        gap: var(--hph-spacing-3);
    }
    
    .hph-filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .hph-search-input {
        min-width: auto;
    }
    
    .hph-listings-grid {
        grid-template-columns: 1fr;
    }
    
    .hph-listing-card-actions {
        flex-wrap: wrap;
    }
}
</style>
