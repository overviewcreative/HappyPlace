<?php
/**
 * Dashboard Listings Management Section
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get all user's listings
$user_listings = get_posts([
    'post_type' => 'listing',
    'author' => get_current_user_id(),
    'post_status' => ['publish', 'draft', 'pending'],
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Group by status
$listings_by_status = [
    'publish' => [],
    'draft' => [],
    'pending' => []
];

foreach ($user_listings as $listing) {
    if (isset($listings_by_status[$listing->post_status])) {
        $listings_by_status[$listing->post_status][] = $listing;
    }
}
?>

<div class="hph-dashboard-listings">
    
    <!-- Listings Header -->
    <div class="listings-header">
        <div class="header-content">
            <h2 class="page-title">My Listings</h2>
            <p class="page-subtitle">Manage your property listings</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo admin_url('post-new.php?post_type=listing'); ?>" class="action-btn action-btn--primary">
                <i class="fas fa-plus"></i> Add New Listing
            </a>
        </div>
    </div>

    <!-- Listings Filters -->
    <div class="listings-filters">
        <div class="filters-row">
            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select class="filter-control" id="status-filter">
                    <option value="">All Statuses</option>
                    <option value="publish">Published (<?php echo count($listings_by_status['publish']); ?>)</option>
                    <option value="draft">Draft (<?php echo count($listings_by_status['draft']); ?>)</option>
                    <option value="pending">Pending (<?php echo count($listings_by_status['pending']); ?>)</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Property Type</label>
                <select class="filter-control" id="type-filter">
                    <option value="">All Types</option>
                    <option value="house">House</option>
                    <option value="condo">Condo</option>
                    <option value="townhouse">Townhouse</option>
                    <option value="land">Land</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Price Range</label>
                <select class="filter-control" id="price-filter">
                    <option value="">All Prices</option>
                    <option value="0-200000">Under $200K</option>
                    <option value="200000-400000">$200K - $400K</option>
                    <option value="400000-600000">$400K - $600K</option>
                    <option value="600000+">Over $600K</option>
                </select>
            </div>
            
            <div class="filters-actions">
                <button type="button" class="action-btn action-btn--secondary" onclick="clearAllFilters()">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Listings Table -->
    <div class="listings-table-container">
        <table class="hph-dashboard-table listings-table">
            <thead>
                <tr>
                    <th class="col-image">Image</th>
                    <th class="col-title">Property</th>
                    <th class="col-price">Price</th>
                    <th class="col-details">Details</th>
                    <th class="col-status">Status</th>
                    <th class="col-date">Date</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($user_listings)) : ?>
                    <?php foreach ($user_listings as $listing) : 
                        $price = get_post_meta($listing->ID, '_listing_price', true);
                        $bedrooms = get_post_meta($listing->ID, '_listing_bedrooms', true);
                        $bathrooms = get_post_meta($listing->ID, '_listing_bathrooms', true);
                        $sqft = get_post_meta($listing->ID, '_listing_square_footage', true);
                    ?>
                        <tr class="listing-row" data-status="<?php echo esc_attr($listing->post_status); ?>">
                            <td class="col-image">
                                <?php if (has_post_thumbnail($listing->ID)) : ?>
                                    <a href="<?php echo get_permalink($listing->ID); ?>">
                                        <?php echo get_the_post_thumbnail($listing->ID, 'thumbnail', ['class' => 'listing-thumbnail']); ?>
                                    </a>
                                <?php else : ?>
                                    <div class="placeholder-thumbnail">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <td class="col-title">
                                <div class="listing-title">
                                    <a href="<?php echo get_permalink($listing->ID); ?>" class="title-link">
                                        <?php echo esc_html($listing->post_title); ?>
                                    </a>
                                </div>
                                <div class="listing-address">
                                    <?php 
                                    $address = get_post_meta($listing->ID, '_listing_address', true);
                                    echo esc_html($address ?: 'Address not set');
                                    ?>
                                </div>
                            </td>
                            
                            <td class="col-price">
                                <?php if ($price) : ?>
                                    <span class="price-value">$<?php echo number_format($price); ?></span>
                                <?php else : ?>
                                    <span class="price-empty">Not set</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="col-details">
                                <div class="property-details">
                                    <?php if ($bedrooms) : ?>
                                        <span class="detail-item"><?php echo esc_html($bedrooms); ?> bed</span>
                                    <?php endif; ?>
                                    <?php if ($bathrooms) : ?>
                                        <span class="detail-item"><?php echo esc_html($bathrooms); ?> bath</span>
                                    <?php endif; ?>
                                    <?php if ($sqft) : ?>
                                        <span class="detail-item"><?php echo number_format($sqft); ?> sqft</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <td class="col-status">
                                <span class="status-badge status-badge--<?php echo esc_attr($listing->post_status); ?>">
                                    <?php echo ucfirst($listing->post_status); ?>
                                </span>
                            </td>
                            
                            <td class="col-date">
                                <span class="date-value"><?php echo get_the_date('M j, Y', $listing->ID); ?></span>
                                <span class="time-value"><?php echo get_the_date('g:i A', $listing->ID); ?></span>
                            </td>
                            
                            <td class="col-actions">
                                <div class="action-menu">
                                    <button type="button" class="action-menu-trigger">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="action-menu-dropdown">
                                        <a href="<?php echo get_permalink($listing->ID); ?>" class="action-item">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="<?php echo get_edit_post_link($listing->ID); ?>" class="action-item">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($listing->post_status === 'publish') : ?>
                                            <a href="#" class="action-item" onclick="toggleListingStatus(<?php echo $listing->ID; ?>, 'draft')">
                                                <i class="fas fa-eye-slash"></i> Unpublish
                                            </a>
                                        <?php else : ?>
                                            <a href="#" class="action-item" onclick="toggleListingStatus(<?php echo $listing->ID; ?>, 'publish')">
                                                <i class="fas fa-check"></i> Publish
                                            </a>
                                        <?php endif; ?>
                                        <div class="action-divider"></div>
                                        <a href="#" class="action-item action-item--danger" onclick="confirmDeleteListing(<?php echo $listing->ID; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr class="empty-row">
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-home"></i>
                                <h4>No listings found</h4>
                                <p>You haven't created any listings yet.</p>
                                <a href="<?php echo admin_url('post-new.php?post_type=listing'); ?>" class="action-btn action-btn--primary">
                                    Create Your First Listing
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Dashboard Listings JavaScript
function clearAllFilters() {
    document.getElementById('status-filter').value = '';
    document.getElementById('type-filter').value = '';
    document.getElementById('price-filter').value = '';
    
    // Show all rows
    const rows = document.querySelectorAll('.listing-row');
    rows.forEach(row => row.style.display = '');
}

function toggleListingStatus(listingId, newStatus) {
    if (confirm('Are you sure you want to change the status of this listing?')) {
        // AJAX call to update status
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=hph_toggle_listing_status&listing_id=${listingId}&new_status=${newStatus}&nonce=${hph_dashboard.nonce}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating listing status');
            }
        });
    }
}

function confirmDeleteListing(listingId) {
    if (confirm('Are you sure you want to delete this listing? This cannot be undone.')) {
        // AJAX call to delete listing
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=hph_delete_listing&listing_id=${listingId}&nonce=${hph_dashboard.nonce}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting listing');
            }
        });
    }
}

// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const filters = ['status-filter', 'type-filter', 'price-filter'];
    
    filters.forEach(filterId => {
        document.getElementById(filterId).addEventListener('change', function() {
            applyFilters();
        });
    });
});

function applyFilters() {
    const statusFilter = document.getElementById('status-filter').value;
    const typeFilter = document.getElementById('type-filter').value;
    const priceFilter = document.getElementById('price-filter').value;
    
    const rows = document.querySelectorAll('.listing-row');
    
    rows.forEach(row => {
        let showRow = true;
        
        // Status filter
        if (statusFilter && row.dataset.status !== statusFilter) {
            showRow = false;
        }
        
        // Add other filter logic here as needed
        
        row.style.display = showRow ? '' : 'none';
    });
}
</script>
