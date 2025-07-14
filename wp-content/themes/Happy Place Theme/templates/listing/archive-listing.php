<?php

/**
 * Archive Template for Property Listings
 * 
 * This template displays the main property listings archive with advanced filtering,
 * search functionality, view modes, and sorting options.
 * 
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get current query vars and sanitize them
$current_filters = [
    'price_min' => isset($_GET['price_min']) ? absint($_GET['price_min']) : '',
    'price_max' => isset($_GET['price_max']) ? absint($_GET['price_max']) : '',
    'bedrooms' => isset($_GET['bedrooms']) ? absint($_GET['bedrooms']) : '',
    'bathrooms' => isset($_GET['bathrooms']) ? absint($_GET['bathrooms']) : '',
    'property_type' => isset($_GET['property_type']) ? sanitize_text_field($_GET['property_type']) : '',
    'location' => isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '',
    'latitude' => isset($_GET['latitude']) ? sanitize_text_field($_GET['latitude']) : '',
    'longitude' => isset($_GET['longitude']) ? sanitize_text_field($_GET['longitude']) : '',
    'features' => isset($_GET['features']) ? array_map('sanitize_text_field', (array)$_GET['features']) : [],
    'sort_by' => isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'newest',
    'view_mode' => isset($_GET['view_mode']) ? sanitize_text_field($_GET['view_mode']) : 'cards',
    'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : ''
];

// Get available filter options
$property_types = get_terms([
    'taxonomy' => 'property_type',
    'hide_empty' => true,
]);

$locations = get_terms([
    'taxonomy' => 'listing_location',
    'hide_empty' => true,
]);

// Build query args
$query_args = [
    'post_type' => 'listing',
    'posts_per_page' => 12,
    'paged' => get_query_var('paged') ?: 1,
    'post_status' => 'publish',
    'meta_query' => ['relation' => 'AND'],
    'tax_query' => ['relation' => 'AND']
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

// Add property type filter
if (!empty($current_filters['property_type'])) {
    $query_args['tax_query'][] = [
        'taxonomy' => 'property_type',
        'field' => 'slug',
        'terms' => $current_filters['property_type']
    ];
}

// Add location filter
if (!empty($current_filters['location'])) {
    $query_args['tax_query'][] = [
        'taxonomy' => 'listing_location',
        'field' => 'slug',
        'terms' => $current_filters['location']
    ];
}

// Add features filter
if (!empty($current_filters['features'])) {
    foreach ($current_filters['features'] as $feature) {
        $query_args['meta_query'][] = [
            'key' => 'features',
            'value' => '"' . $feature . '"',
            'compare' => 'LIKE'
        ];
    }
}

// Add featured listing filter for sorting
if ($current_filters['sort_by'] === 'featured') {
    $query_args['meta_query'][] = [
        'key' => 'featured_listing',
        'value' => '1',
        'compare' => '='
    ];
}

// Add geolocation-based query if coordinates are provided
if (!empty($current_filters['latitude']) && !empty($current_filters['longitude'])) {
    // Convert coordinates to float
    $lat = floatval($current_filters['latitude']);
    $lng = floatval($current_filters['longitude']);

    // Get properties within ~50km radius (approximate using lat/lng boxes)
    $lat_range = 0.45; // About 50km in latitude degrees
    $lng_range = 0.45 / cos(deg2rad($lat)); // Adjust for longitude distance variation

    $query_args['meta_query'][] = [
        'relation' => 'AND',
        [
            'key' => 'latitude',
            'value' => [$lat - $lat_range, $lat + $lat_range],
            'type' => 'DECIMAL(10,8)',
            'compare' => 'BETWEEN'
        ],
        [
            'key' => 'longitude',
            'value' => [$lng - $lng_range, $lng + $lng_range],
            'type' => 'DECIMAL(11,8)',
            'compare' => 'BETWEEN'
        ]
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
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
        break;
    case 'oldest':
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'ASC';
        break;
    case 'featured':
        $query_args['meta_query'][] = [
            'key' => 'highlight_badges',
            'value' => 'featured',
            'compare' => 'LIKE'
        ];
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
        break;
    default:
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
}

// Execute query
$listings_query = new WP_Query($query_args);

// Get total count for display
$total_properties = $listings_query->found_posts;

// Prepare markers data for map view
$markers = [];
if ($current_filters['view_mode'] === 'map' && $listings_query->have_posts()) {
    while ($listings_query->have_posts()) {
        $listings_query->the_post();
        $listing_id = get_the_ID();

        // Get basic listing details
        $price = get_field('price', $listing_id);
        $status = get_field('status', $listing_id);
        $bedrooms = get_field('bedrooms', $listing_id);
        $bathrooms = get_field('bathrooms', $listing_id);
        $square_footage = get_field('square_footage', $listing_id);

        // Get location data
        $street = get_field('street_address', $listing_id);
        $city = get_field('city', $listing_id);
        $region = get_field('region', $listing_id);
        $zip = get_field('zip_code', $listing_id);
        $latitude = get_field('latitude', $listing_id);
        $longitude = get_field('longitude', $listing_id);

        // Skip properties without valid coordinates
        if (!$latitude || !$longitude || !is_numeric($latitude) || !is_numeric($longitude)) {
            continue;
        }

        // Format full address
        $full_address = get_field('full_address', $listing_id);
        if (!$full_address && $street) {
            $address_parts = array_filter([$street, $city, $region, $zip]);
            $full_address = implode(', ', $address_parts);
        }

        // Get main photo
        $main_photo = '';

        // Try main_photo field first
        $main_photo_field = get_field('main_photo', $listing_id);
        if ($main_photo_field) {
            $main_photo = $main_photo_field;
        }

        // If no main photo, try gallery
        if (!$main_photo) {
            $gallery = get_field('photo_gallery', $listing_id);
            if ($gallery && is_array($gallery) && !empty($gallery)) {
                // Gallery returns array of image arrays
                $main_photo = $gallery[0]['sizes']['medium'] ?? $gallery[0]['url'] ?? '';
            }
        }

        // Fallback to featured image
        if (!$main_photo && has_post_thumbnail($listing_id)) {
            $main_photo = get_the_post_thumbnail_url($listing_id, 'medium');
        }

        // Final fallback to placeholder
        if (!$main_photo) {
            $main_photo = get_theme_file_uri('assets/images/property-placeholder.jpg');
        }

        // Get key features
        $features = [];
        $individual_features = get_field('individual_features', $listing_id);
        if ($individual_features) {
            if (!empty($individual_features['garage'])) $features[] = 'Garage';
            if (!empty($individual_features['pool'])) $features[] = 'Pool';
            if (!empty($individual_features['fireplace'])) $features[] = 'Fireplace';
            if (!empty($individual_features['basement'])) $features[] = 'Basement';
            if (!empty($individual_features['deck_patio'])) $features[] = 'Deck/Patio';
        }

        // Build marker data
        $markers[] = [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => $price,
            'bedrooms' => $bedrooms,
            'bathrooms' => $bathrooms,
            'square_footage' => $square_footage,
            'status' => $status,
            'latitude' => floatval($latitude),
            'longitude' => floatval($longitude),
            'permalink' => get_permalink($listing_id),
            'address' => $full_address,
            'photo' => $main_photo,
            'features' => $features
        ];
    }
    wp_reset_postdata();
}

// Features options
$features = [
    'pool' => 'Pool',
    'garage' => 'Garage',
    'fireplace' => 'Fireplace',
    'waterfront' => 'Waterfront',
    'view' => 'View',
    'basement' => 'Basement'
];
?>

<div class="hph-listings-archive">
    <!-- Hero Search Section -->
    <section class="hph-archive-hero">
        <div class="hph-container">
            <div class="hph-hero-content">
                <h1 class="hph-hero-title">
                    <?php
                    if (!empty($current_filters['search']) || array_filter($current_filters)) {
                        printf(__('Search Results', 'happy-place'));
                    } else {
                        printf(__('Find Your Perfect Home', 'happy-place'));
                    }
                    ?>
                </h1>
                <p class="hph-hero-subtitle">
                    <?php
                    printf(
                        _n('%d property available', '%d properties available', $total_properties, 'happy-place'),
                        $total_properties
                    );
                    ?>
                </p>

                <!-- Quick Search Bar -->
                <div class="hph-quick-search">
                    <form method="get" class="hph-search-form">
                        <div class="hph-search-input-group">
                            <input type="text"
                                id="hph-location-search"
                                name="search"
                                placeholder="<?php esc_attr_e('Search by address, city, or ZIP code...', 'happy-place'); ?>"
                                value="<?php echo esc_attr($current_filters['search']); ?>"
                                class="hph-search-input">

                            <!-- Hidden fields for geocoded data -->
                            <input type="hidden" id="hph-search-latitude" name="latitude" value="<?php echo esc_attr($current_filters['latitude']); ?>">
                            <input type="hidden" id="hph-search-longitude" name="longitude" value="<?php echo esc_attr($current_filters['longitude']); ?>">

                            <button type="submit" class="hph-search-btn">
                                <i class="fas fa-search"></i>
                                <span class="sr-only"><?php esc_html_e('Search', 'happy-place'); ?></span>
                            </button>
                        </div>

                        <!-- Property Type & Status Quick Filters -->
                        <div class="hph-quick-filters">
                            <?php if (!empty($property_types)) : ?>
                                <select name="property_type" class="hph-quick-select">
                                    <option value=""><?php esc_html_e('Any Property Type', 'happy-place'); ?></option>
                                    <?php foreach ($property_types as $type) : ?>
                                        <option value="<?php echo esc_attr($type->slug); ?>"
                                            <?php selected($current_filters['property_type'], $type->slug); ?>>
                                            <?php echo esc_html($type->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>

                            <select name="status" class="hph-quick-select">
                                <option value=""><?php esc_html_e('Any Status', 'happy-place'); ?></option>
                                <option value="active" <?php selected($current_filters['status'], 'active'); ?>>
                                    <?php esc_html_e('Active', 'happy-place'); ?>
                                </option>
                                <option value="pending" <?php selected($current_filters['status'], 'pending'); ?>>
                                    <?php esc_html_e('Pending', 'happy-place'); ?>
                                </option>
                                <option value="sold" <?php selected($current_filters['status'], 'sold'); ?>>
                                    <?php esc_html_e('Sold', 'happy-place'); ?>
                                </option>
                                <option value="coming-soon" <?php selected($current_filters['status'], 'coming-soon'); ?>>
                                    <?php esc_html_e('Coming Soon', 'happy-place'); ?>
                                </option>
                            </select>
                        </div>

                        <!-- Preserve other filters -->
                        <?php foreach ($current_filters as $key => $value) : ?>
                            <?php if (!in_array($key, ['search', 'property_type', 'status', 'latitude', 'longitude']) && !empty($value)) : ?>
                                <?php if (is_array($value)) : ?>
                                    <?php foreach ($value as $val) : ?>
                                        <input type="hidden" name="<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr($val); ?>">
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <div class="hph-container">
        <?php if ($current_filters['view_mode'] === 'map') : ?>
            <!-- Map View Layout -->
    </div>
    <div class="hph-map-fullwidth-container">
        <!-- Debug Output -->
        <?php if (isset($_GET['debug'])) : ?>
            <script>
                console.log('Map Markers Data:', <?php echo json_encode($markers); ?>);
            </script>
        <?php endif; ?>

        <!-- Map Container -->
        <div id="listingsMap" class="hph-listings-map"
            data-properties='<?php
                                // Ensure proper JSON encoding and escaping
                                echo esc_attr(wp_json_encode($markers, JSON_HEX_APOS | JSON_HEX_QUOT));
                                ?>'
            data-clusterer="true"
            data-fit-bounds="true">
        </div>

        <!-- Map Sidebar -->
        <div class="hph-map-sidebar">
            <!-- Filters Accordion -->
            <div class="hph-map-filters">
                <div class="hph-filters-header">
                    <h3><?php esc_html_e('Filter Properties', 'happy-place'); ?></h3>
                    <?php if (array_filter($current_filters)) : ?>
                        <a href="<?php echo add_query_arg('view_mode', 'map', get_post_type_archive_link('listing')); ?>" class="hph-clear-filters">
                            <?php esc_html_e('Clear All', 'happy-place'); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <form method="get" class="hph-filters-form" id="mapFiltersForm">
                    <!-- Preserve view mode -->
                    <input type="hidden" name="view_mode" value="map">

                    <!-- Preserve search query -->
                    <?php if (!empty($current_filters['search'])) : ?>
                        <input type="hidden" name="search" value="<?php echo esc_attr($current_filters['search']); ?>">
                    <?php endif; ?>

                    <!-- Preserve location data -->
                    <?php if (!empty($current_filters['latitude']) && !empty($current_filters['longitude'])) : ?>
                        <input type="hidden" name="latitude" value="<?php echo esc_attr($current_filters['latitude']); ?>">
                        <input type="hidden" name="longitude" value="<?php echo esc_attr($current_filters['longitude']); ?>">
                    <?php endif; ?>

                    <!-- Price Range - Always Visible -->
                    <div class="hph-filter-section hph-filter-section--open">
                        <h4 class="hph-filter-title"><?php esc_html_e('Price Range', 'happy-place'); ?></h4>
                        <div class="hph-filter-content">
                            <div class="hph-price-inputs">
                                <select class="hph-price-select" name="price_min">
                                    <option value="">Min Price</option>
                                    <option value="0" <?php selected($current_filters['price_min'], '0'); ?>>$0</option>
                                    <option value="50000" <?php selected($current_filters['price_min'], '50000'); ?>>$50K</option>
                                    <option value="100000" <?php selected($current_filters['price_min'], '100000'); ?>>$100K</option>
                                    <option value="200000" <?php selected($current_filters['price_min'], '200000'); ?>>$200K</option>
                                    <option value="300000" <?php selected($current_filters['price_min'], '300000'); ?>>$300K</option>
                                    <option value="400000" <?php selected($current_filters['price_min'], '400000'); ?>>$400K</option>
                                    <option value="500000" <?php selected($current_filters['price_min'], '500000'); ?>>$500K</option>
                                    <option value="600000" <?php selected($current_filters['price_min'], '600000'); ?>>$600K</option>
                                    <option value="700000" <?php selected($current_filters['price_min'], '700000'); ?>>$700K</option>
                                    <option value="800000" <?php selected($current_filters['price_min'], '800000'); ?>>$800K</option>
                                    <option value="900000" <?php selected($current_filters['price_min'], '900000'); ?>>$900K</option>
                                    <option value="1000000" <?php selected($current_filters['price_min'], '1000000'); ?>>$1M</option>
                                </select>
                                <span class="hph-price-separator">to</span>
                                <select class="hph-price-select" name="price_max">
                                    <option value="">Max Price</option>
                                    <option value="100000" <?php selected($current_filters['price_max'], '100000'); ?>>$100K</option>
                                    <option value="200000" <?php selected($current_filters['price_max'], '200000'); ?>>$200K</option>
                                    <option value="300000" <?php selected($current_filters['price_max'], '300000'); ?>>$300K</option>
                                    <option value="400000" <?php selected($current_filters['price_max'], '400000'); ?>>$400K</option>
                                    <option value="500000" <?php selected($current_filters['price_max'], '500000'); ?>>$500K</option>
                                    <option value="600000" <?php selected($current_filters['price_max'], '600000'); ?>>$600K</option>
                                    <option value="700000" <?php selected($current_filters['price_max'], '700000'); ?>>$700K</option>
                                    <option value="800000" <?php selected($current_filters['price_max'], '800000'); ?>>$800K</option>
                                    <option value="900000" <?php selected($current_filters['price_max'], '900000'); ?>>$900K</option>
                                    <option value="1000000" <?php selected($current_filters['price_max'], '1000000'); ?>>$1M</option>
                                    <option value="1500000" <?php selected($current_filters['price_max'], '1500000'); ?>>$1.5M</option>
                                    <option value="2000000" <?php selected($current_filters['price_max'], '2000000'); ?>>$2M</option>
                                    <option value="2000001" <?php selected($current_filters['price_max'], '2000001'); ?>>$2M+</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Bedrooms - Always Visible -->
                    <div class="hph-filter-section hph-filter-section--open">
                        <h4 class="hph-filter-title"><?php esc_html_e('Bedrooms', 'happy-place'); ?></h4>
                        <div class="hph-filter-content">
                            <div class="hph-bedroom-options">
                                <label class="hph-bedroom-option <?php echo empty($current_filters['bedrooms']) ? 'active' : ''; ?>">
                                    <input type="radio" name="bedrooms" value="" <?php checked(empty($current_filters['bedrooms'])); ?>>
                                    <span>Any</span>
                                </label>
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <label class="hph-bedroom-option <?php echo $current_filters['bedrooms'] == $i ? 'active' : ''; ?>">
                                        <input type="radio" name="bedrooms" value="<?php echo esc_attr($i); ?>"
                                            <?php checked($current_filters['bedrooms'], $i); ?>>
                                        <span><?php echo $i; ?><?php echo $i == 5 ? '+' : ''; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Bathrooms - Always Visible -->
                    <div class="hph-filter-section hph-filter-section--open">
                        <h4 class="hph-filter-title"><?php esc_html_e('Bathrooms', 'happy-place'); ?></h4>
                        <div class="hph-filter-content">
                            <div class="hph-bathroom-options">
                                <label class="hph-bathroom-option <?php echo empty($current_filters['bathrooms']) ? 'active' : ''; ?>">
                                    <input type="radio" name="bathrooms" value="" <?php checked(empty($current_filters['bathrooms'])); ?>>
                                    <span>Any</span>
                                </label>
                                <?php for ($i = 1; $i <= 4; $i++) : ?>
                                    <label class="hph-bathroom-option <?php echo $current_filters['bathrooms'] == $i ? 'active' : ''; ?>">
                                        <input type="radio" name="bathrooms" value="<?php echo esc_attr($i); ?>"
                                            <?php checked($current_filters['bathrooms'], $i); ?>>
                                        <span><?php echo $i; ?><?php echo $i == 4 ? '+' : ''; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Property Type - Collapsible -->
                    <?php if (!empty($property_types)) : ?>
                        <div class="hph-filter-section">
                            <h4 class="hph-filter-title hph-filter-toggle">
                                <span><?php esc_html_e('Property Type', 'happy-place'); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </h4>
                            <div class="hph-filter-content hph-filter-content--collapsed">
                                <select name="property_type" class="hph-filter-select">
                                    <option value=""><?php esc_html_e('Any Type', 'happy-place'); ?></option>
                                    <?php foreach ($property_types as $type) : ?>
                                        <option value="<?php echo esc_attr($type->slug); ?>"
                                            <?php selected($current_filters['property_type'], $type->slug); ?>>
                                            <?php echo esc_html($type->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Location - Collapsible -->
                    <?php if (!empty($locations)) : ?>
                        <div class="hph-filter-section">
                            <h4 class="hph-filter-title hph-filter-toggle">
                                <span><?php esc_html_e('Location', 'happy-place'); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </h4>
                            <div class="hph-filter-content hph-filter-content--collapsed">
                                <select name="location" class="hph-filter-select">
                                    <option value=""><?php esc_html_e('Any Location', 'happy-place'); ?></option>
                                    <?php foreach ($locations as $location) : ?>
                                        <option value="<?php echo esc_attr($location->slug); ?>"
                                            <?php selected($current_filters['location'], $location->slug); ?>>
                                            <?php echo esc_html($location->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Features - Collapsible -->
                    <div class="hph-filter-section">
                        <h4 class="hph-filter-title hph-filter-toggle">
                            <span><?php esc_html_e('Features', 'happy-place'); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </h4>
                        <div class="hph-filter-content hph-filter-content--collapsed">
                            <div class="hph-features-options">
                                <?php foreach ($features as $key => $label) : ?>
                                    <label class="hph-feature-option <?php echo in_array($key, $current_filters['features']) ? 'active' : ''; ?>">
                                        <input type="checkbox" name="features[]" value="<?php echo esc_attr($key); ?>"
                                            <?php checked(in_array($key, $current_filters['features'])); ?>>
                                        <span><?php echo esc_html($label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="hph-filter-actions">
                        <button type="submit" class="hph-btn hph-btn--primary hph-btn--full">
                            <?php esc_html_e('Apply Filters', 'happy-place'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Map Listings -->
            <div class="hph-map-listings">
                <?php if ($listings_query->have_posts()) : ?>
                    <?php while ($listings_query->have_posts()) : $listings_query->the_post(); ?>
                        <div class="hph-map-listing-card" data-listing-id="<?php echo get_the_ID(); ?>">
                            <?php
                            get_template_part('templates/template-parts/cards/listing-list-card', null, [
                                'post_id' => get_the_ID(),
                                'size' => 'compact'
                            ]);
                            ?>
                        </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="hph-no-results">
                        <p><?php esc_html_e('No properties found matching your criteria.', 'happy-place'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="hph-container">
    <?php else : ?>
        <!-- Standard Layout (List or Card View) -->
        <div class="hph-listings-layout">
            <!-- Sidebar Filters -->
            <aside class="hph-listings-sidebar">
                <div class="hph-filters-container">
                    <div class="hph-filters-header">
                        <h3><?php esc_html_e('Filter Properties', 'happy-place'); ?></h3>
                        <?php if (array_filter($current_filters)) : ?>
                            <a href="<?php echo get_post_type_archive_link('listing'); ?>" class="hph-clear-filters">
                                <?php esc_html_e('Clear All', 'happy-place'); ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <form method="get" class="hph-filters-form" id="listingFilters">
                        <!-- Preserve view mode -->
                        <input type="hidden" name="view_mode" value="<?php echo esc_attr($current_filters['view_mode']); ?>">

                        <!-- Preserve search query -->
                        <?php if (!empty($current_filters['search'])) : ?>
                            <input type="hidden" name="search" value="<?php echo esc_attr($current_filters['search']); ?>">
                        <?php endif; ?>

                        <!-- Price Range -->
                        <div class="hph-filter-group">
                            <h4 class="hph-filter-title"><?php esc_html_e('Price Range', 'happy-place'); ?></h4>
                            <div class="hph-price-inputs">
                                <select class="hph-price-select" name="price_min">
                                    <option value="">Min Price</option>
                                    <option value="0" <?php selected($current_filters['price_min'], '0'); ?>>$0</option>
                                    <option value="50000" <?php selected($current_filters['price_min'], '50000'); ?>>$50K</option>
                                    <option value="100000" <?php selected($current_filters['price_min'], '100000'); ?>>$100K</option>
                                    <option value="200000" <?php selected($current_filters['price_min'], '200000'); ?>>$200K</option>
                                    <option value="300000" <?php selected($current_filters['price_min'], '300000'); ?>>$300K</option>
                                    <option value="400000" <?php selected($current_filters['price_min'], '400000'); ?>>$400K</option>
                                    <option value="500000" <?php selected($current_filters['price_min'], '500000'); ?>>$500K</option>
                                    <option value="600000" <?php selected($current_filters['price_min'], '600000'); ?>>$600K</option>
                                    <option value="700000" <?php selected($current_filters['price_min'], '700000'); ?>>$700K</option>
                                    <option value="800000" <?php selected($current_filters['price_min'], '800000'); ?>>$800K</option>
                                    <option value="900000" <?php selected($current_filters['price_min'], '900000'); ?>>$900K</option>
                                    <option value="1000000" <?php selected($current_filters['price_min'], '1000000'); ?>>$1M</option>
                                </select>
                                <span class="hph-price-separator">to</span>
                                <select class="hph-price-select" name="price_max">
                                    <option value="">Max Price</option>
                                    <option value="100000" <?php selected($current_filters['price_max'], '100000'); ?>>$100K</option>
                                    <option value="200000" <?php selected($current_filters['price_max'], '200000'); ?>>$200K</option>
                                    <option value="300000" <?php selected($current_filters['price_max'], '300000'); ?>>$300K</option>
                                    <option value="400000" <?php selected($current_filters['price_max'], '400000'); ?>>$400K</option>
                                    <option value="500000" <?php selected($current_filters['price_max'], '500000'); ?>>$500K</option>
                                    <option value="600000" <?php selected($current_filters['price_max'], '600000'); ?>>$600K</option>
                                    <option value="700000" <?php selected($current_filters['price_max'], '700000'); ?>>$700K</option>
                                    <option value="800000" <?php selected($current_filters['price_max'], '800000'); ?>>$800K</option>
                                    <option value="900000" <?php selected($current_filters['price_max'], '900000'); ?>>$900K</option>
                                    <option value="1000000" <?php selected($current_filters['price_max'], '1000000'); ?>>$1M</option>
                                    <option value="1500000" <?php selected($current_filters['price_max'], '1500000'); ?>>$1.5M</option>
                                    <option value="2000000" <?php selected($current_filters['price_max'], '2000000'); ?>>$2M</option>
                                    <option value="2000001" <?php selected($current_filters['price_max'], '2000001'); ?>>$2M+</option>
                                </select>
                            </div>
                        </div>

                        <!-- Property Type -->
                        <?php if (!empty($property_types)) : ?>
                            <div class="hph-filter-group">
                                <h4 class="hph-filter-title"><?php esc_html_e('Property Type', 'happy-place'); ?></h4>
                                <select name="property_type" class="hph-filter-select">
                                    <option value=""><?php esc_html_e('Any Type', 'happy-place'); ?></option>
                                    <?php foreach ($property_types as $type) : ?>
                                        <option value="<?php echo esc_attr($type->slug); ?>"
                                            <?php selected($current_filters['property_type'], $type->slug); ?>>
                                            <?php echo esc_html($type->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- Location -->
                        <?php if (!empty($locations)) : ?>
                            <div class="hph-filter-group">
                                <h4 class="hph-filter-title"><?php esc_html_e('Location', 'happy-place'); ?></h4>
                                <select name="location" class="hph-filter-select">
                                    <option value=""><?php esc_html_e('Any Location', 'happy-place'); ?></option>
                                    <?php foreach ($locations as $location) : ?>
                                        <option value="<?php echo esc_attr($location->slug); ?>"
                                            <?php selected($current_filters['location'], $location->slug); ?>>
                                            <?php echo esc_html($location->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- Bedrooms -->
                        <div class="hph-filter-group">
                            <h4 class="hph-filter-title"><?php esc_html_e('Bedrooms', 'happy-place'); ?></h4>
                            <div class="hph-bedroom-options">
                                <label class="hph-bedroom-option <?php echo empty($current_filters['bedrooms']) ? 'active' : ''; ?>">
                                    <input type="radio" name="bedrooms" value="" <?php checked(empty($current_filters['bedrooms'])); ?>>
                                    <span>Any</span>
                                </label>
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <label class="hph-bedroom-option <?php echo $current_filters['bedrooms'] == $i ? 'active' : ''; ?>">
                                        <input type="radio" name="bedrooms" value="<?php echo esc_attr($i); ?>"
                                            <?php checked($current_filters['bedrooms'], $i); ?>>
                                        <span><?php echo $i; ?><?php echo $i == 5 ? '+' : ''; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Bathrooms -->
                        <div class="hph-filter-group">
                            <h4 class="hph-filter-title"><?php esc_html_e('Bathrooms', 'happy-place'); ?></h4>
                            <div class="hph-bathroom-options">
                                <label class="hph-bathroom-option <?php echo empty($current_filters['bathrooms']) ? 'active' : ''; ?>">
                                    <input type="radio" name="bathrooms" value="" <?php checked(empty($current_filters['bathrooms'])); ?>>
                                    <span>Any</span>
                                </label>
                                <?php for ($i = 1; $i <= 4; $i++) : ?>
                                    <label class="hph-bathroom-option <?php echo $current_filters['bathrooms'] == $i ? 'active' : ''; ?>">
                                        <input type="radio" name="bathrooms" value="<?php echo esc_attr($i); ?>"
                                            <?php checked($current_filters['bathrooms'], $i); ?>>
                                        <span><?php echo $i; ?><?php echo $i == 4 ? '+' : ''; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="hph-filter-group">
                            <h4 class="hph-filter-title"><?php esc_html_e('Features', 'happy-place'); ?></h4>
                            <div class="hph-features-options">
                                <?php foreach ($features as $key => $label) : ?>
                                    <label class="hph-feature-option <?php echo in_array($key, $current_filters['features']) ? 'active' : ''; ?>">
                                        <input type="checkbox" name="features[]" value="<?php echo esc_attr($key); ?>"
                                            <?php checked(in_array($key, $current_filters['features'])); ?>>
                                        <span><?php echo esc_html($label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="hph-filter-actions">
                            <button type="submit" class="hph-btn hph-btn--primary hph-btn--full">
                                <?php esc_html_e('Apply Filters', 'happy-place'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="hph-listings-main">
                <!-- Results Header -->
                <div class="hph-results-header">
                    <div class="hph-results-info">
                        <h2 class="hph-results-count">
                            <?php
                            printf(
                                _n('%d Property Found', '%d Properties Found', $total_properties, 'happy-place'),
                                $total_properties
                            );
                            ?>
                        </h2>

                        <!-- Active Filters -->
                        <?php if (array_filter($current_filters)) : ?>
                            <div class="hph-active-filters">
                                <?php if (!empty($current_filters['search'])) : ?>
                                    <span class="hph-filter-tag">
                                        <?php printf(__('Search: "%s"', 'happy-place'), esc_html($current_filters['search'])); ?>
                                        <a href="<?php echo remove_query_arg('search'); ?>" class="hph-remove-filter">×</a>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($current_filters['price_min']) || !empty($current_filters['price_max'])) : ?>
                                    <span class="hph-filter-tag">
                                        <?php
                                        if (!empty($current_filters['price_min']) && !empty($current_filters['price_max'])) {
                                            printf(
                                                __('Price: $%s - $%s', 'happy-place'),
                                                number_format($current_filters['price_min']),
                                                number_format($current_filters['price_max'])
                                            );
                                        } elseif (!empty($current_filters['price_min'])) {
                                            printf(__('Price: $%s+', 'happy-place'), number_format($current_filters['price_min']));
                                        } else {
                                            printf(__('Price: Under $%s', 'happy-place'), number_format($current_filters['price_max']));
                                        }
                                        ?>
                                        <a href="<?php echo remove_query_arg(['price_min', 'price_max']); ?>" class="hph-remove-filter">×</a>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($current_filters['bedrooms'])) : ?>
                                    <span class="hph-filter-tag">
                                        <?php printf(__('%d+ Bedrooms', 'happy-place'), $current_filters['bedrooms']); ?>
                                        <a href="<?php echo remove_query_arg('bedrooms'); ?>" class="hph-remove-filter">×</a>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($current_filters['bathrooms'])) : ?>
                                    <span class="hph-filter-tag">
                                        <?php printf(__('%d+ Bathrooms', 'happy-place'), $current_filters['bathrooms']); ?>
                                        <a href="<?php echo remove_query_arg('bathrooms'); ?>" class="hph-remove-filter">×</a>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($current_filters['property_type'])) :
                                    $term = get_term_by('slug', $current_filters['property_type'], 'property_type');
                                    if ($term) : ?>
                                        <span class="hph-filter-tag">
                                            <?php echo esc_html($term->name); ?>
                                            <a href="<?php echo remove_query_arg('property_type'); ?>" class="hph-remove-filter">×</a>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php foreach ($current_filters['features'] as $feature) :
                                    if (isset($features[$feature])) : ?>
                                        <span class="hph-filter-tag">
                                            <?php echo esc_html($features[$feature]); ?>
                                            <a href="<?php echo remove_query_arg(['features' => $feature]); ?>" class="hph-remove-filter">×</a>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="hph-results-controls">
                        <!-- View Mode Toggle -->
                        <div class="hph-view-toggle">
                            <a href="<?php echo add_query_arg('view_mode', 'cards'); ?>"
                                class="hph-view-btn <?php echo $current_filters['view_mode'] === 'cards' ? 'active' : ''; ?>"
                                title="<?php esc_attr_e('Card View', 'happy-place'); ?>">
                                <i class="fas fa-th-large"></i>
                            </a>
                            <a href="<?php echo add_query_arg('view_mode', 'list'); ?>"
                                class="hph-view-btn <?php echo $current_filters['view_mode'] === 'list' ? 'active' : ''; ?>"
                                title="<?php esc_attr_e('List View', 'happy-place'); ?>">
                                <i class="fas fa-list"></i>
                            </a>
                            <a href="<?php echo add_query_arg('view_mode', 'map'); ?>"
                                class="hph-view-btn <?php echo $current_filters['view_mode'] === 'map' ? 'active' : ''; ?>"
                                title="<?php esc_attr_e('Map View', 'happy-place'); ?>">
                                <i class="fas fa-map-marked-alt"></i>
                            </a>
                        </div>

                        <!-- Sort Options -->
                        <div class="hph-sort-controls">
                            <label for="sortBy" class="sr-only"><?php esc_html_e('Sort by', 'happy-place'); ?></label>
                            <select name="sort_by" id="sortBy" class="hph-sort-select">
                                <option value="newest" <?php selected($current_filters['sort_by'], 'newest'); ?>>
                                    <?php esc_html_e('Newest First', 'happy-place'); ?>
                                </option>
                                <option value="price_low" <?php selected($current_filters['sort_by'], 'price_low'); ?>>
                                    <?php esc_html_e('Price: Low to High', 'happy-place'); ?>
                                </option>
                                <option value="price_high" <?php selected($current_filters['sort_by'], 'price_high'); ?>>
                                    <?php esc_html_e('Price: High to Low', 'happy-place'); ?>
                                </option>
                                <option value="featured" <?php selected($current_filters['sort_by'], 'featured'); ?>>
                                    <?php esc_html_e('Featured First', 'happy-place'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Results Content -->
                <div class="hph-results-content">
                    <?php if ($listings_query->have_posts()) : ?>

                        <?php if ($current_filters['view_mode'] === 'list') : ?>
                            <!-- List View -->
                            <div class="hph-listings-list">
                                <?php while ($listings_query->have_posts()) : $listings_query->the_post(); ?>
                                    <article class="hph-listing-list-item">
                                        <?php
                                        get_template_part('templates/template-parts/cards/listing-list-card', null, [
                                            'post_id' => get_the_ID(),
                                            'size' => 'default',
                                            'show_agent' => true
                                        ]);
                                        ?>
                                    </article>
                                <?php endwhile; ?>
                            </div>

                        <?php else : ?>
                            <!-- Card View (Default) -->
                            <div class="hph-listings-grid">
                                <?php while ($listings_query->have_posts()) : $listings_query->the_post(); ?>
                                    <div class="hph-listing-card-wrapper">
                                        <?php
                                        get_template_part('templates/template-parts/cards/listing-swipe-card', null, [
                                            'post_id' => get_the_ID(),
                                            'size' => 'default'
                                        ]);
                                        ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>

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
                                'prev_text' => '<i class="fas fa-chevron-left"></i> ' . __('Previous', 'happy-place'),
                                'next_text' => __('Next', 'happy-place') . ' <i class="fas fa-chevron-right"></i>',
                                'add_args' => array_filter($current_filters),
                                'class' => 'hph-pagination'
                            ];

                            echo paginate_links($pagination_args);
                            ?>
                        </div>

                    <?php else : ?>
                        <!-- No Results -->
                        <div class="hph-no-results">
                            <div class="hph-no-results-content">
                                <i class="fas fa-search hph-no-results-icon"></i>
                                <h3 class="hph-no-results-title"><?php esc_html_e('No Properties Found', 'happy-place'); ?></h3>
                                <p class="hph-no-results-message">
                                    <?php esc_html_e('We couldn\'t find any properties matching your search criteria. Try adjusting your filters or search terms.', 'happy-place'); ?>
                                </p>
                                <div class="hph-no-results-actions">
                                    <a href="<?php echo get_post_type_archive_link('listing'); ?>" class="hph-btn hph-btn--primary">
                                        <?php esc_html_e('View All Properties', 'happy-place'); ?>
                                    </a>
                                    <button type="button" class="hph-btn hph-btn--secondary" onclick="document.getElementById('listingFilters').reset();">
                                        <?php esc_html_e('Clear Filters', 'happy-place'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    <?php endif; ?>
    </div>
</div>

<?php
wp_reset_postdata();
get_footer();
?>