<?php
/**
 * Dashboard Listings Management Section with Form Integration
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current user ID
$current_user_id = get_current_user_id();

// Get agent post for current user 
$agent_id = 0;
$agents = get_posts([
    'post_type' => 'agent',
    'meta_key' => 'user_id',
    'meta_value' => $current_user_id,
    'posts_per_page' => 1
]);

if (!empty($agents)) {
    $agent_id = $agents[0]->ID;
}

// Get user's listings using bridge functions
$user_listings = [];
if ($agent_id) {
    // Get listings for this agent
    $listings_query = new WP_Query([
        'post_type' => 'listing',
        'post_status' => ['publish', 'draft', 'pending'],
        'meta_query' => [
            [
                'key' => 'listing_agent',
                'value' => $agent_id,
                'compare' => '='
            ]
        ],
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
    
    if ($listings_query->have_posts()) {
        $user_listings = $listings_query->posts;
    }
    wp_reset_postdata();
}

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

// Get open houses for user's listings
$open_houses = [];
if (!empty($user_listings)) {
    $listing_ids = array_map(function($listing) { return $listing->ID; }, $user_listings);
    
    $open_houses_query = new WP_Query([
        'post_type' => 'open-house',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'listing',
                'value' => $listing_ids,
                'compare' => 'IN'
            ],
            [
                'key' => 'open_house_date',
                'value' => date('Y-m-d'),
                'compare' => '>='
            ]
        ],
        'meta_key' => 'open_house_date',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'posts_per_page' => 5
    ]);
    
    if ($open_houses_query->have_posts()) {
        $open_houses = $open_houses_query->posts;
    }
    wp_reset_postdata();
}
?>

<div class="hph-section-modern">
    
    <!-- Listings Header -->
    <div class="hph-section-header">
        <div class="hph-section-title">
            <div class="title-icon">
                <i class="fas fa-home"></i>
            </div>
            <h2 class="title-text">My Listings</h2>
        </div>
        <p class="hph-section-subtitle">Manage your property listings and track performance</p>
        <div class="hph-section-actions">
            <button type="button" class="hph-btn hph-btn--modern hph-btn--gradient" onclick="openListingForm(0)">
                <i class="fas fa-plus"></i> Add New Listing
            </button>
        </div>
    </div>

    <!-- Listings Stats -->
    <div class="hph-section-body">
        <div class="hph-stats-grid">
            <div class="hph-stat-card hph-stat-card--info">
                <div class="hph-stat-content">
                    <div class="hph-stat-data">
                        <div class="hph-stat-value"><?php echo count($listings_by_status['publish']); ?></div>
                        <div class="hph-stat-label">Active</div>
                    </div>
                    <div class="hph-stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
            </div>
            
            <div class="hph-stat-card hph-stat-card--warning">
                <div class="hph-stat-content">
                    <div class="hph-stat-data">
                        <div class="hph-stat-value"><?php echo count($listings_by_status['draft']); ?></div>
                        <div class="hph-stat-label">Draft</div>
                    </div>
                    <div class="hph-stat-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                </div>
            </div>
            
            <div class="hph-stat-card hph-stat-card--success">
                <div class="hph-stat-content">
                    <div class="hph-stat-data">
                        <div class="hph-stat-value"><?php echo count($listings_by_status['pending']); ?></div>
                        <div class="hph-stat-label">Pending</div>
                    </div>
                    <div class="hph-stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            
            <div class="hph-stat-card hph-stat-card--primary">
                <div class="hph-stat-content">
                    <div class="hph-stat-data">
                        <div class="hph-stat-value"><?php echo count($open_houses); ?></div>
                        <div class="hph-stat-label">Upcoming Open Houses</div>
                    </div>
                    <div class="hph-stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="hph-content-card">
            <div class="hph-content-header">
                <div class="content-title">
                    <h3 class="title-text">Quick Actions</h3>
                </div>
            </div>
            <div class="hph-content-body hph-content-body--compact">
                <div class="hph-actions-grid">
                    <button type="button" class="hph-action-btn" onclick="openListingForm(0)">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Listing</span>
                    </button>
                    <button type="button" class="hph-action-btn" onclick="openOpenHouseForm(0)">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Schedule Open House</span>
                    </button>
                    <button type="button" class="hph-action-btn" onclick="importFromMLS()">
                        <i class="fas fa-download"></i>
                        <span>Import from MLS</span>
                    </button>
                    <button type="button" class="hph-action-btn" onclick="exportListings()">
                        <i class="fas fa-upload"></i>
                        <span>Export Listings</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Upcoming Open Houses -->
        <?php if (!empty($open_houses)) : ?>
        <div class="hph-content-card">
            <div class="hph-content-header">
                <div class="content-title">
                    <h3 class="title-text">Upcoming Open Houses</h3>
                    <span class="title-badge"><?php echo count($open_houses); ?></span>
                </div>
            </div>
            <div class="hph-content-body hph-content-body--compact">
                <div class="hph-list-modern">
                    <?php foreach ($open_houses as $open_house) : 
                        $listing_id = get_field('listing', $open_house->ID);
                        $open_date = get_field('open_house_date', $open_house->ID);
                        $start_time = get_field('start_time', $open_house->ID);
                        $end_time = get_field('end_time', $open_house->ID);
                        $listing_title = $listing_id ? get_the_title($listing_id) : 'Unknown Property';
                    ?>
                        <div class="list-item">
                            <div class="item-icon">
                                <i class="fas fa-calendar-alt" style="color: var(--hph-primary);"></i>
                            </div>
                            <div class="item-content">
                                <div class="item-title"><?php echo esc_html($open_house->post_title); ?></div>
                                <div class="item-subtitle"><?php echo esc_html($listing_title); ?></div>
                                <div class="item-meta">
                                    <span class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M j, Y', strtotime($open_date)); ?>
                                    </span>
                                    <span class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time)); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="item-actions">
                                <button type="button" class="hph-btn hph-btn--modern hph-btn--sm" onclick="openOpenHouseForm(<?php echo $open_house->ID; ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Listings Grid -->
        <div class="hph-content-card">
            <div class="hph-content-header">
                <div class="content-title">
                    <h3 class="title-text">Your Listings</h3>
                    <span class="title-badge"><?php echo count($user_listings); ?></span>
                </div>
                <p class="content-subtitle">Manage and track your property listings</p>
                <div class="content-actions">
                    <div class="hph-form-inline">
                        <select class="form-control form-control--sm" id="status-filter" onchange="applyFilters()">
                            <option value="">All Statuses</option>
                            <option value="publish">Published</option>
                            <option value="draft">Draft</option>
                            <option value="pending">Pending</option>
                        </select>
                        <button type="button" class="hph-btn hph-btn--modern hph-btn--sm" onclick="clearAllFilters()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="hph-content-body hph-content-body--compact">
                <?php if (!empty($user_listings)) : ?>
                    <div class="hph-list-modern" id="listings-container">
                        <?php foreach ($user_listings as $listing) : 
                            // Use bridge functions to get listing data
                            $listing_data = function_exists('hph_bridge_get_listing_data') ? 
                                hph_bridge_get_listing_data($listing->ID) : [];
                            
                            // Fallback to ACF fields if bridge function not available
                            $price = !empty($listing_data['price']) ? $listing_data['price'] : get_field('price', $listing->ID);
                            $bedrooms = !empty($listing_data['bedrooms']) ? $listing_data['bedrooms'] : get_field('bedrooms', $listing->ID);
                            $bathrooms = !empty($listing_data['bathrooms']) ? $listing_data['bathrooms'] : get_field('bathrooms', $listing->ID);
                            $square_footage = !empty($listing_data['square_footage']) ? $listing_data['square_footage'] : get_field('square_footage', $listing->ID);
                            $street_address = get_field('street_address', $listing->ID);
                            $city = get_field('city', $listing->ID);
                            $state = get_field('state', $listing->ID);
                            $mls_number = get_field('mls_number', $listing->ID);
                            
                            $address_parts = array_filter([$street_address, $city, $state]);
                            $full_address = implode(', ', $address_parts);
                        ?>
                            <div class="list-item list-item--clickable" 
                                 data-status="<?php echo esc_attr($listing->post_status); ?>" 
                                 data-listing-id="<?php echo $listing->ID; ?>"
                                 data-price="<?php echo esc_attr($price ?: 0); ?>">
                                
                                <div class="item-icon">
                                    <?php if (has_post_thumbnail($listing->ID)) : ?>
                                        <a href="<?php echo get_permalink($listing->ID); ?>" target="_blank">
                                            <?php echo get_the_post_thumbnail($listing->ID, 'thumbnail', [
                                                'style' => 'width: 50px; height: 50px; object-fit: cover; border-radius: 8px;'
                                            ]); ?>
                                        </a>
                                    <?php else : ?>
                                        <div class="item-icon-placeholder" style="color: var(--hph-gray-400);">
                                            <i class="fas fa-home"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-content">
                                    <div class="item-title">
                                        <a href="<?php echo get_permalink($listing->ID); ?>" class="item-title-link" target="_blank">
                                            <?php echo esc_html($listing->post_title); ?>
                                        </a>
                                        <?php if ($mls_number) : ?>
                                            <span class="item-mls">MLS# <?php echo esc_html($mls_number); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-subtitle">
                                        <?php echo esc_html($full_address ?: 'Address not set'); ?>
                                    </div>
                                    <div class="item-meta">
                                        <?php if ($price) : ?>
                                            <span class="meta-item meta-price">$<?php echo number_format($price); ?></span>
                                        <?php endif; ?>
                                        <?php if ($bedrooms) : ?>
                                            <span class="meta-item"><?php echo esc_html($bedrooms); ?> bed</span>
                                        <?php endif; ?>
                                        <?php if ($bathrooms) : ?>
                                            <span class="meta-item"><?php echo esc_html($bathrooms); ?> bath</span>
                                        <?php endif; ?>
                                        <?php if ($square_footage) : ?>
                                            <span class="meta-item"><?php echo number_format($square_footage); ?> sqft</span>
                                        <?php endif; ?>
                                        <span class="meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo get_the_date('M j, Y', $listing->ID); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="item-actions">
                                    <span class="item-status item-status--<?php 
                                        echo $listing->post_status === 'publish' ? 'active' : 
                                             ($listing->post_status === 'pending' ? 'pending' : 'inactive');
                                    ?>">
                                        <?php echo ucfirst($listing->post_status); ?>
                                    </span>
                                    
                                    <button type="button" class="hph-btn hph-btn--modern hph-btn--sm" onclick="openListingForm(<?php echo $listing->ID; ?>)" title="Edit Listing">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <div class="dropdown">
                                        <button type="button" class="hph-btn hph-btn--modern hph-btn--sm dropdown-toggle" onclick="toggleDropdown(<?php echo $listing->ID; ?>)" title="More actions">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu" id="dropdown-<?php echo $listing->ID; ?>">
                                            <a href="<?php echo get_permalink($listing->ID); ?>" target="_blank" class="dropdown-item">
                                                <i class="fas fa-eye"></i> View Listing
                                            </a>
                                            <a href="#" onclick="openOpenHouseForm(0, <?php echo $listing->ID; ?>)" class="dropdown-item">
                                                <i class="fas fa-calendar-plus"></i> Schedule Open House
                                            </a>
                                            <a href="#" onclick="duplicateListing(<?php echo $listing->ID; ?>)" class="dropdown-item">
                                                <i class="fas fa-copy"></i> Duplicate
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <?php if ($listing->post_status === 'publish') : ?>
                                                <a href="#" onclick="toggleListingStatus(<?php echo $listing->ID; ?>, 'draft')" class="dropdown-item">
                                                    <i class="fas fa-eye-slash"></i> Unpublish
                                                </a>
                                            <?php else : ?>
                                                <a href="#" onclick="toggleListingStatus(<?php echo $listing->ID; ?>, 'publish')" class="dropdown-item">
                                                    <i class="fas fa-check"></i> Publish
                                                </a>
                                            <?php endif; ?>
                                            <div class="dropdown-divider"></div>
                                            <a href="#" onclick="confirmDeleteListing(<?php echo $listing->ID; ?>)" class="dropdown-item dropdown-item--danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="hph-empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h4 class="empty-title">No listings found</h4>
                        <p class="empty-description">Get started by creating your first property listing to showcase to potential buyers.</p>
                        <div class="empty-actions">
                            <button type="button" class="hph-btn hph-btn--modern hph-btn--gradient" onclick="openListingForm(0)">
                                <i class="fas fa-plus"></i> Create Your First Listing
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Listing Form Modal -->
<div id="listing-form-modal" class="hph-modal" style="display: none;">
    <div class="hph-modal-backdrop" onclick="closeListingForm()"></div>
    <div class="hph-modal-content hph-modal-content--large">
        <div class="hph-modal-header">
            <h3 class="hph-modal-title" id="listing-form-title">Add New Listing</h3>
            <button type="button" class="hph-modal-close" onclick="closeListingForm()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hph-modal-body" id="listing-form-container">
            <!-- Form content will be loaded here -->
        </div>
    </div>
</div>

<!-- Open House Form Modal -->
<div id="open-house-form-modal" class="hph-modal" style="display: none;">
    <div class="hph-modal-backdrop" onclick="closeOpenHouseForm()"></div>
    <div class="hph-modal-content">
        <div class="hph-modal-header">
            <h3 class="hph-modal-title" id="open-house-form-title">Schedule Open House</h3>
            <button type="button" class="hph-modal-close" onclick="closeOpenHouseForm()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hph-modal-body" id="open-house-form-container">
            <!-- Form content will be loaded here -->
        </div>
    </div>
</div>

<script>
// Dashboard Listings JavaScript with Form Integration
let currentOpenDropdown = null;

// Form Management Functions
function openListingForm(listingId = 0) {
    const modal = document.getElementById('listing-form-modal');
    const title = document.getElementById('listing-form-title');
    const container = document.getElementById('listing-form-container');
    
    title.textContent = listingId ? 'Edit Listing' : 'Add New Listing';
    container.innerHTML = '<div class="hph-loading"><i class="fas fa-spinner fa-spin"></i> Loading form...</div>';
    
    modal.style.display = 'flex';
    
    // Load form via AJAX
    fetch(HphDashboard.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=hph_load_listing_form&listing_id=${listingId}&nonce=${HphDashboard.nonce}`
    })
    .then(response => response.text())
    .then(html => {
        container.innerHTML = html;
        initializeListingForm();
    })
    .catch(error => {
        console.error('Error loading form:', error);
        container.innerHTML = '<div class="hph-error">Error loading form. Please try again.</div>';
    });
}

function closeListingForm() {
    document.getElementById('listing-form-modal').style.display = 'none';
}

function openOpenHouseForm(openHouseId = 0, listingId = 0) {
    const modal = document.getElementById('open-house-form-modal');
    const title = document.getElementById('open-house-form-title');
    const container = document.getElementById('open-house-form-container');
    
    title.textContent = openHouseId ? 'Edit Open House' : 'Schedule Open House';
    container.innerHTML = '<div class="hph-loading"><i class="fas fa-spinner fa-spin"></i> Loading form...</div>';
    
    modal.style.display = 'flex';
    
    // Load form via AJAX
    fetch(HphDashboard.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=hph_load_open_house_form&open_house_id=${openHouseId}&listing_id=${listingId}&nonce=${HphDashboard.nonce}`
    })
    .then(response => response.text())
    .then(html => {
        container.innerHTML = html;
        initializeOpenHouseForm();
    })
    .catch(error => {
        console.error('Error loading form:', error);
        container.innerHTML = '<div class="hph-error">Error loading form. Please try again.</div>';
    });
}

function closeOpenHouseForm() {
    document.getElementById('open-house-form-modal').style.display = 'none';
}

// Initialize form functionality
function initializeListingForm() {
    // Add form submission handling, field calculations, etc.
    const form = document.querySelector('#listing-form-container form');
    if (form) {
        form.addEventListener('submit', handleListingFormSubmit);
    }
}

function initializeOpenHouseForm() {
    // Add form submission handling for open house
    const form = document.querySelector('#open-house-form-container form');
    if (form) {
        form.addEventListener('submit', handleOpenHouseFormSubmit);
    }
}

function handleListingFormSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'hph_save_listing');
    formData.append('nonce', HphDashboard.nonce);
    
    // Show loading state
    const submitBtn = e.target.querySelector('[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;
    
    fetch(HphDashboard.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeListingForm();
            location.reload(); // Refresh to show updated listing
        } else {
            alert('Error saving listing: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the listing.');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function handleOpenHouseFormSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'hph_save_open_house');
    formData.append('nonce', HphDashboard.nonce);
    
    // Show loading state
    const submitBtn = e.target.querySelector('[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scheduling...';
    submitBtn.disabled = true;
    
    fetch(HphDashboard.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeOpenHouseForm();
            location.reload(); // Refresh to show new open house
        } else {
            alert('Error scheduling open house: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while scheduling the open house.');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Dropdown Management
function toggleDropdown(listingId) {
    const dropdown = document.getElementById(`dropdown-${listingId}`);
    
    // Close any other open dropdowns
    if (currentOpenDropdown && currentOpenDropdown !== dropdown) {
        currentOpenDropdown.style.display = 'none';
    }
    
    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
        currentOpenDropdown = null;
    } else {
        dropdown.style.display = 'block';
        currentOpenDropdown = dropdown;
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        if (currentOpenDropdown) {
            currentOpenDropdown.style.display = 'none';
            currentOpenDropdown = null;
        }
    }
});

// Listing Actions
function confirmDeleteListing(listingId) {
    if (confirm('Are you sure you want to delete this listing? This action cannot be undone.')) {
        fetch(HphDashboard.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=hph_delete_listing&listing_id=${listingId}&nonce=${HphDashboard.nonce}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const listingItem = document.querySelector(`[data-listing-id="${listingId}"]`);
                if (listingItem) {
                    listingItem.remove();
                }
                showNotification('Listing deleted successfully.', 'success');
            } else {
                showNotification('Error deleting listing: ' + (data.data || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while deleting the listing.', 'error');
        });
    }
}

function toggleListingStatus(listingId, newStatus) {
    if (confirm(`Are you sure you want to ${newStatus === 'publish' ? 'publish' : 'unpublish'} this listing?`)) {
        fetch(HphDashboard.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=hph_toggle_listing_status&listing_id=${listingId}&new_status=${newStatus}&nonce=${HphDashboard.nonce}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showNotification('Error updating listing status', 'error');
            }
        });
    }
}

function duplicateListing(listingId) {
    if (confirm('This will create a copy of the listing as a draft. Continue?')) {
        fetch(HphDashboard.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=hph_duplicate_listing&listing_id=${listingId}&nonce=${HphDashboard.nonce}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showNotification('Error duplicating listing: ' + (data.data || 'Unknown error'), 'error');
            }
        });
    }
}

// Filter and Search Functions
function clearAllFilters() {
    document.getElementById('status-filter').value = '';
    applyFilters();
}

function applyFilters() {
    const statusFilter = document.getElementById('status-filter').value;
    const items = document.querySelectorAll('#listings-container .list-item');
    
    items.forEach(item => {
        let showItem = true;
        
        if (statusFilter && item.dataset.status !== statusFilter) {
            showItem = false;
        }
        
        item.style.display = showItem ? '' : 'none';
    });
}

// Import/Export Functions
function importFromMLS() {
    // MLS import functionality with proper implementation
    if (confirm('Start MLS import? This will sync listings from your MLS system.')) {
        showNotification('Starting MLS import...', 'info');
        
        // Show loading state
        const importBtn = document.querySelector('[onclick="importFromMLS()"]');
        const originalText = importBtn.textContent;
        importBtn.disabled = true;
        importBtn.textContent = 'Importing...';
        
        // Make AJAX request for MLS import
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hph_import_from_mls',
                nonce: window.hphAjax?.nonce || ''
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`MLS import completed! ${data.data.count || 0} listings imported.`, 'success');
                // Refresh the listings display
                location.reload();
            } else {
                showNotification(data.data?.message || 'MLS import failed. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('MLS import error:', error);
            showNotification('MLS import failed. Please check your connection and try again.', 'error');
        })
        .finally(() => {
            importBtn.disabled = false;
            importBtn.textContent = originalText;
        });
    }
}

function exportListings() {
    // Export functionality with multiple format support
    const exportModal = document.createElement('div');
    exportModal.className = 'hph-modal hph-export-modal';
    exportModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Export Listings</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="export-options">
                    <h4>Export Format</h4>
                    <label><input type="radio" name="export_format" value="csv" checked> CSV (Spreadsheet)</label>
                    <label><input type="radio" name="export_format" value="pdf"> PDF Report</label>
                    <label><input type="radio" name="export_format" value="xml"> XML Feed</label>
                    <label><input type="radio" name="export_format" value="json"> JSON Data</label>
                    
                    <h4>Filter Options</h4>
                    <label><input type="checkbox" name="export_filter" value="active" checked> Active Listings Only</label>
                    <label><input type="checkbox" name="export_filter" value="include_images"> Include Image URLs</label>
                    <label><input type="checkbox" name="export_filter" value="include_agent"> Include Agent Info</label>
                    
                    <h4>Date Range</h4>
                    <div class="date-range">
                        <input type="date" name="start_date" placeholder="Start Date">
                        <input type="date" name="end_date" placeholder="End Date">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-cancel">Cancel</button>
                <button type="button" class="btn btn-primary export-confirm">Export</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(exportModal);
    
    // Handle modal close
    exportModal.querySelector('.modal-close').addEventListener('click', () => {
        exportModal.remove();
    });
    
    exportModal.querySelector('.modal-cancel').addEventListener('click', () => {
        exportModal.remove();
    });
    
    // Handle export
    exportModal.querySelector('.export-confirm').addEventListener('click', () => {
        const format = exportModal.querySelector('input[name="export_format"]:checked').value;
        const filters = Array.from(exportModal.querySelectorAll('input[name="export_filter"]:checked')).map(cb => cb.value);
        const startDate = exportModal.querySelector('input[name="start_date"]').value;
        const endDate = exportModal.querySelector('input[name="end_date"]').value;
        
        // Start export process
        const exportBtn = exportModal.querySelector('.export-confirm');
        exportBtn.disabled = true;
        exportBtn.textContent = 'Exporting...';
        
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'hph_export_listings',
                format: format,
                filters: JSON.stringify(filters),
                start_date: startDate,
                end_date: endDate,
                nonce: window.hphAjax?.nonce || ''
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.download_url) {
                // Trigger download
                const link = document.createElement('a');
                link.href = data.data.download_url;
                link.download = data.data.filename || `listings_export.${format}`;
                link.click();
                
                showNotification('Export completed successfully!', 'success');
                exportModal.remove();
            } else {
                showNotification(data.data?.message || 'Export failed. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Export error:', error);
            showNotification('Export failed. Please check your connection and try again.', 'error');
        })
        .finally(() => {
            exportBtn.disabled = false;
            exportBtn.textContent = 'Export';
        });
    });
}

// Notification System
function showNotification(message, type = 'info') {
    // Simple notification - in production you'd want a proper notification system
    const notification = document.createElement('div');
    notification.className = `hph-notification hph-notification--${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button type="button" class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Initialize filters on page load
document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }
});
</script>

<style>
/* Additional styles for the enhanced listings interface */
.hph-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.hph-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background: var(--hph-white);
    border: 1px solid var(--hph-gray-200);
    border-radius: 8px;
    text-decoration: none;
    color: var(--hph-gray-700);
    transition: all 0.2s ease;
    cursor: pointer;
}

.hph-action-btn:hover {
    background: var(--hph-gray-50);
    border-color: var(--hph-primary);
    color: var(--hph-primary);
    transform: translateY(-2px);
}

.hph-action-btn i {
    font-size: 1.5rem;
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: var(--hph-white);
    border: 1px solid var(--hph-gray-200);
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    min-width: 180px;
    z-index: 1000;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    color: var(--hph-gray-700);
    text-decoration: none;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.dropdown-item:hover {
    background: var(--hph-gray-50);
}

.dropdown-item--danger {
    color: var(--hph-danger);
}

.dropdown-item--danger:hover {
    background: var(--hph-danger-light);
}

.dropdown-divider {
    height: 1px;
    background: var(--hph-gray-200);
    margin: 0.25rem 0;
}

.item-mls {
    font-size: 0.75rem;
    color: var(--hph-gray-500);
    margin-left: 0.5rem;
}

.meta-price {
    font-weight: 600;
    color: var(--hph-primary);
}

.hph-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.hph-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
}

.hph-modal-content {
    position: relative;
    background: var(--hph-white);
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    max-width: 600px;
    width: 100%;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.hph-modal-content--large {
    max-width: 900px;
}

.hph-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--hph-gray-200);
}

.hph-modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.hph-modal-close {
    background: none;
    border: none;
    color: var(--hph-gray-500);
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
}

.hph-modal-close:hover {
    background: var(--hph-gray-100);
    color: var(--hph-gray-700);
}

.hph-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 2rem;
}

.hph-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--hph-gray-500);
}

.hph-error {
    color: var(--hph-danger);
    text-align: center;
    padding: 2rem;
}

.hph-notification {
    position: fixed;
    top: 2rem;
    right: 2rem;
    background: var(--hph-white);
    border: 1px solid var(--hph-gray-200);
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    z-index: 10000;
    display: flex;
    align-items: center;
    padding: 1rem;
    min-width: 300px;
}

.hph-notification--success {
    border-left: 4px solid var(--hph-success);
}

.hph-notification--error {
    border-left: 4px solid var(--hph-danger);
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
}

.notification-close {
    background: none;
    border: none;
    color: var(--hph-gray-500);
    cursor: pointer;
    padding: 0.25rem;
}
</style>