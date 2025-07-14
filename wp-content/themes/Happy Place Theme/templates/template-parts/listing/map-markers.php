<?php
/**
 * Template part for displaying listing markers
 */

// Prepare markers data for map view
$markers = [];
if ($current_filters['view_mode'] === 'map' && $listings_query->have_posts()) {
    while ($listings_query->have_posts()) {
        $listings_query->the_post();
        $marker_data = hph_listing()->get_map_data(get_the_ID());
        
        // Skip listings without coordinates
        if (!empty($marker_data)) {
            $markers[] = $marker_data;
        }
    }
    wp_reset_postdata();
}

// Convert markers to JSON for map
$markers_json = wp_json_encode($markers);
