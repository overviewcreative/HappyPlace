<?php
/**
 * Enhanced Archive Template for Property Listings
 * Following the legacy template structure with improved design
 * 
 * @package Happy_Place_Theme
 */

get_header();

// Get current query vars and sanitize them
$current_filters = [
    'price_min' => isset($_GET['price_min']) ? absint($_GET['price_min']) : '',
    'price_max' => isset($_GET['price_max']) ? absint($_GET['price_max']) : '',
    'bedrooms' => isset($_GET['bedrooms']) ? absint($_GET['bedrooms']) : '',
    'bathrooms' => isset($_GET['bathrooms']) ? absint($_GET['bathrooms']) : '',
    'property_type' => isset($_GET['property_type']) ? sanitize_text_field($_GET['property_type']) : '',
    'location' => isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '',
    'sort_by' => isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'newest',
    'view_mode' => isset($_GET['view_mode']) ? sanitize_text_field($_GET['view_mode']) : 'cards',
    'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : ''
];

// Build query args
$query_args = [
    'post_type' => 'listing',
    'posts_per_page' => 12,
    'paged' => get_query_var('paged') ?: 1,
    'post_status' => 'publish',
    'meta_query' => ['relation' => 'AND']
];

// Add search query
if (!empty($current_filters['search'])) {
    $query_args['s'] = $current_filters['search'];
}

// Add price filters
if (!empty($current_filters['price_min'])) {
    $query_args['meta_query'][] = [
        'key' => 'price',
        'value' => $current_filters['price_min'],
        'type' => 'NUMERIC',
        'compare' => '>='
    ];
}

if (!empty($current_filters['price_max'])) {
    $query_args['meta_query'][] = [
        'key' => 'price',
        'value' => $current_filters['price_max'],
        'type' => 'NUMERIC',
        'compare' => '<='
    ];
}

// Add bedroom filter
if (!empty($current_filters['bedrooms'])) {
    $query_args['meta_query'][] = [
        'key' => 'bedrooms',
        'value' => $current_filters['bedrooms'],
        'type' => 'NUMERIC',
        'compare' => '>='
    ];
}

// Add bathroom filter
if (!empty($current_filters['bathrooms'])) {
    $query_args['meta_query'][] = [
        'key' => 'bathrooms',
        'value' => $current_filters['bathrooms'],
        'type' => 'NUMERIC',
        'compare' => '>='
    ];
}

// Add sorting
switch ($current_filters['sort_by']) {
    case 'price_low':
        $query_args['meta_key'] = 'price';
        $query_args['orderby'] = 'meta_value_num';
        $query_args['order'] = 'ASC';
        break;
    case 'price_high':
        $query_args['meta_key'] = 'price';
        $query_args['orderby'] = 'meta_value_num';
        $query_args['order'] = 'DESC';
        break;
    case 'newest':
    default:
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
        break;
}

// Execute query
$listings_query = new WP_Query($query_args);
?>

<main class="archive-listing">
    <!-- Archive Header -->
    <div class="hph-archive-header">
        <div class="container">
            <h1 class="hph-archive-title">Property Listings</h1>
            <p class="hph-archive-description">
                Find your perfect home from our collection of premium properties
            </p>
        </div>
    </div>

    <div class="container">
        <!-- Filters Section -->
        <div class="hph-filters">
            <form class="hph-filters-form" method="GET">
                <div class="hph-filter-group">
                    <label class="hph-filter-label">Search</label>
                    <input type="text" 
                           name="search" 
                           class="hph-input" 
                           placeholder="Enter keywords..."
                           value="<?php echo esc_attr($current_filters['search']); ?>">
                </div>

                <div class="hph-filter-group">
                    <label class="hph-filter-label">Min Price</label>
                    <select name="price_min" class="hph-select">
                        <option value="">Any Price</option>
                        <option value="100000" <?php selected($current_filters['price_min'], '100000'); ?>>$100,000+</option>
                        <option value="200000" <?php selected($current_filters['price_min'], '200000'); ?>>$200,000+</option>
                        <option value="300000" <?php selected($current_filters['price_min'], '300000'); ?>>$300,000+</option>
                        <option value="400000" <?php selected($current_filters['price_min'], '400000'); ?>>$400,000+</option>
                        <option value="500000" <?php selected($current_filters['price_min'], '500000'); ?>>$500,000+</option>
                    </select>
                </div>

                <div class="hph-filter-group">
                    <label class="hph-filter-label">Max Price</label>
                    <select name="price_max" class="hph-select">
                        <option value="">Any Price</option>
                        <option value="200000" <?php selected($current_filters['price_max'], '200000'); ?>>$200,000</option>
                        <option value="300000" <?php selected($current_filters['price_max'], '300000'); ?>>$300,000</option>
                        <option value="400000" <?php selected($current_filters['price_max'], '400000'); ?>>$400,000</option>
                        <option value="500000" <?php selected($current_filters['price_max'], '500000'); ?>>$500,000</option>
                        <option value="1000000" <?php selected($current_filters['price_max'], '1000000'); ?>>$1,000,000</option>
                    </select>
                </div>

                <div class="hph-filter-group">
                    <label class="hph-filter-label">Bedrooms</label>
                    <select name="bedrooms" class="hph-select">
                        <option value="">Any</option>
                        <option value="1" <?php selected($current_filters['bedrooms'], '1'); ?>>1+</option>
                        <option value="2" <?php selected($current_filters['bedrooms'], '2'); ?>>2+</option>
                        <option value="3" <?php selected($current_filters['bedrooms'], '3'); ?>>3+</option>
                        <option value="4" <?php selected($current_filters['bedrooms'], '4'); ?>>4+</option>
                        <option value="5" <?php selected($current_filters['bedrooms'], '5'); ?>>5+</option>
                    </select>
                </div>

                <div class="hph-filter-group">
                    <label class="hph-filter-label">Bathrooms</label>
                    <select name="bathrooms" class="hph-select">
                        <option value="">Any</option>
                        <option value="1" <?php selected($current_filters['bathrooms'], '1'); ?>>1+</option>
                        <option value="2" <?php selected($current_filters['bathrooms'], '2'); ?>>2+</option>
                        <option value="3" <?php selected($current_filters['bathrooms'], '3'); ?>>3+</option>
                        <option value="4" <?php selected($current_filters['bathrooms'], '4'); ?>>4+</option>
                    </select>
                </div>

                <div class="hph-filter-group">
                    <label class="hph-filter-label">Sort By</label>
                    <select name="sort_by" class="hph-select">
                        <option value="newest" <?php selected($current_filters['sort_by'], 'newest'); ?>>Newest First</option>
                        <option value="price_low" <?php selected($current_filters['sort_by'], 'price_low'); ?>>Price: Low to High</option>
                        <option value="price_high" <?php selected($current_filters['sort_by'], 'price_high'); ?>>Price: High to Low</option>
                    </select>
                </div>

                <div class="hph-filter-group">
                    <button type="submit" class="btn btn-primary">Search Properties</button>
                </div>
            </form>
        </div>

        <!-- Results Header -->
        <div class="hph-results-header">
            <div class="hph-results-count">
                <strong><?php echo $listings_query->found_posts; ?></strong> properties found
            </div>
            <div class="hph-view-toggle">
                <a href="?<?php echo http_build_query(array_merge($current_filters, ['view_mode' => 'cards'])); ?>" 
                   class="view-toggle <?php echo $current_filters['view_mode'] === 'cards' ? 'active' : ''; ?>">
                    Grid View
                </a>
                <a href="?<?php echo http_build_query(array_merge($current_filters, ['view_mode' => 'list'])); ?>" 
                   class="view-toggle <?php echo $current_filters['view_mode'] === 'list' ? 'active' : ''; ?>">
                    List View
                </a>
            </div>
        </div>

        <!-- Listings Grid -->
        <div class="hph-listings-container <?php echo esc_attr($current_filters['view_mode']); ?>-view">
            <?php if ($listings_query->have_posts()) : ?>
                <div class="hph-listings-grid">
                    <?php while ($listings_query->have_posts()) : $listings_query->the_post(); 
                        $listing_id = get_the_ID();
                        $price = get_field('price') ?: 450000;
                        $status = get_field('status') ?: 'For Sale';
                        $bedrooms = get_field('bedrooms') ?: 4;
                        $bathrooms = get_field('bathrooms') ?: 3;
                        $square_feet = get_field('square_feet') ?: 2450;
                        $address = get_field('address') ?: '123 Main Street, Your City, ST';
                        $is_favorite = false; // Will be dynamic with user system
                    ?>
                        <div class="hph-listing-card">
                            <div class="hph-listing-card-inner">
                                <!-- Image Section -->
                                <div class="hph-listing-image">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <img src="<?php the_post_thumbnail_url('medium_large'); ?>" 
                                                 alt="<?php the_title_attribute(); ?>"
                                                 loading="lazy">
                                        <?php else : ?>
                                            <div class="image-placeholder">
                                                <span>📷 No Image</span>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <!-- Status Badge -->
                                    <div class="status-badge status-badge--<?php echo esc_attr(strtolower(str_replace(' ', '-', $status))); ?>">
                                        <?php echo esc_html($status); ?>
                                    </div>
                                    
                                    <!-- Favorite Button -->
                                    <button class="favorite-button <?php echo $is_favorite ? 'favorite-button--active' : ''; ?>" 
                                            data-listing-id="<?php echo esc_attr($listing_id); ?>">
                                        <?php echo $is_favorite ? '♥' : '♡'; ?>
                                    </button>
                                </div>
                                
                                <!-- Content Section -->
                                <div class="hph-listing-content">
                                    <h3 class="hph-listing-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                    <div class="hph-listing-price">
                                        $<?php echo number_format($price); ?>
                                    </div>
                                    
                                    <div class="hph-listing-meta">
                                        📍 <?php echo esc_html($address); ?>
                                    </div>
                                    
                                    <div class="hph-listing-stats">
                                        <div class="stat">
                                            <span class="stat-number"><?php echo esc_html($bedrooms); ?></span>
                                            <span class="stat-label">beds</span>
                                        </div>
                                        <div class="stat">
                                            <span class="stat-number"><?php echo esc_html($bathrooms); ?></span>
                                            <span class="stat-label">baths</span>
                                        </div>
                                        <div class="stat">
                                            <span class="stat-number"><?php echo number_format($square_feet); ?></span>
                                            <span class="stat-label">sqft</span>
                                        </div>
                                    </div>
                                    
                                    <div class="hph-listing-actions">
                                        <a href="<?php the_permalink(); ?>" class="btn btn-primary">View Details</a>
                                        <button class="btn btn-secondary contact-agent-btn" 
                                                data-listing-id="<?php echo esc_attr($listing_id); ?>">
                                            Contact Agent
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <div class="hph-pagination-wrapper">
                    <?php 
                    $pagination_args = [
                        'total' => $listings_query->max_num_pages,
                        'current' => max(1, get_query_var('paged')),
                        'format' => '?paged=%#%',
                        'show_all' => false,
                        'end_size' => 1,
                        'mid_size' => 2,
                        'prev_next' => true,
                        'prev_text' => '← Previous',
                        'next_text' => 'Next →',
                        'add_args' => array_filter($current_filters)
                    ];
                    
                    echo paginate_links($pagination_args);
                    ?>
                </div>

            <?php else : ?>
                <div class="no-listings-found">
                    <h3>No Properties Found</h3>
                    <p>Try adjusting your search criteria or browse all available properties.</p>
                    <a href="<?php echo get_post_type_archive_link('listing'); ?>" class="btn btn-primary">
                        View All Properties
                    </a>
                </div>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Favorite button functionality
    const favoriteButtons = document.querySelectorAll('.favorite-button');
    favoriteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const listingId = this.dataset.listingId;
            const isActive = this.classList.contains('favorite-button--active');
            
            // Optimistic UI update
            this.classList.toggle('favorite-button--active');
            this.textContent = isActive ? '♡' : '♥';
            
            // Send AJAX request (placeholder)
            console.log('Favorite toggled for listing:', listingId);
        });
    });
    
    // Contact agent buttons
    const contactButtons = document.querySelectorAll('.contact-agent-btn');
    contactButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const listingId = this.dataset.listingId;
            
            // Show contact modal or redirect
            alert('Contact agent functionality for listing: ' + listingId);
        });
    });
    
    // Auto-submit form on filter change (optional)
    const filterForm = document.querySelector('.hph-filters-form');
    const autoSubmitSelects = filterForm.querySelectorAll('select');
    
    autoSubmitSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Optional: Auto-submit on change
            // filterForm.submit();
        });
    });
});
</script>

<?php get_footer(); ?>
