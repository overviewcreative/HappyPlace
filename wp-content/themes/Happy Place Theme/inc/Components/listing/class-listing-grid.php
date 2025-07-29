<?php
/**
 * Listing Grid Component
 *
 * Grid display component for multiple property listings.
 *
 * @package HappyPlace\Components\Listing
 * @since 2.0.0
 */

namespace HappyPlace\Components\Listing;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Listing_Grid extends Base_Component {
    
    /**
     * Component name identifier
     *
     * @return string
     */
    protected function get_component_name() {
        return 'listing-grid';
    }
    
    /**
     * Default component properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            'listings' => [],
            'limit' => 12,
            'columns' => 3,
            'columns_mobile' => 1,
            'columns_tablet' => 2,
            'show_pagination' => true,
            'show_filters' => false,
            'show_sorting' => false,
            'card_style' => 'standard', // standard, compact, featured
            'aspect_ratio' => '16:9',
            'hover_effect' => 'lift',
            'lazy_load' => true,
            'agent_id' => 0,
            'property_type' => '',
            'status' => 'active',
            'price_min' => 0,
            'price_max' => 0,
            'bedrooms_min' => 0,
            'bathrooms_min' => 0,
            'city' => '',
            'state' => '',
            'zip' => '',
            'sort_by' => 'price_desc',
            'page' => 1,
            'load_more' => false,
            'show_total_count' => true,
            'container_class' => '',
            'enable_favorites' => true,
            'enable_quick_view' => false
        ];
    }
    
    /**
     * Render the component
     *
     * @return string
     */
    protected function render() {
        $listings = $this->get_listings();
        $total_count = $this->get_total_count();
        
        if (empty($listings)) {
            return $this->render_no_results();
        }
        
        $grid_classes = $this->get_grid_classes();
        $container_class = $this->get_prop('container_class');
        
        ob_start();
        ?>
        <div class="hph-listing-grid <?php echo esc_attr($grid_classes . ' ' . $container_class); ?>" 
             data-component="listing-grid"
             data-columns="<?php echo esc_attr($this->get_prop('columns')); ?>"
             data-card-style="<?php echo esc_attr($this->get_prop('card_style')); ?>"
             data-hover-effect="<?php echo esc_attr($this->get_prop('hover_effect')); ?>">
            
            <?php if ($this->get_prop('show_total_count') && $total_count > 0): ?>
                <div class="hph-grid-header">
                    <div class="hph-grid-count">
                        <?php printf(_n('%d property found', '%d properties found', $total_count, 'happy-place'), $total_count); ?>
                    </div>
                    
                    <?php if ($this->get_prop('show_sorting')): ?>
                        <?php $this->render_sorting_controls(); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($this->get_prop('show_filters')): ?>
                <?php $this->render_filters(); ?>
            <?php endif; ?>
            
            <div class="hph-listing-grid__container">
                <?php foreach ($listings as $listing): ?>
                    <?php $this->render_listing_card($listing); ?>
                <?php endforeach; ?>
            </div>
            
            <?php if ($this->get_prop('show_pagination') && $total_count > $this->get_prop('limit')): ?>
                <?php $this->render_pagination($total_count); ?>
            <?php endif; ?>
            
            <?php if ($this->get_prop('load_more') && $this->has_more_listings($total_count)): ?>
                <?php $this->render_load_more_button(); ?>
            <?php endif; ?>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get listings based on component props
     */
    private function get_listings() {
        $listings = $this->get_prop('listings');
        
        // If listings are provided directly, use them
        if (!empty($listings)) {
            return array_slice($listings, 0, $this->get_prop('limit'));
        }
        
        // Otherwise query listings based on filters
        $args = [
            'limit' => $this->get_prop('limit'),
            'page' => $this->get_prop('page'),
            'sort_by' => $this->get_prop('sort_by'),
            'status' => $this->get_prop('status')
        ];
        
        // Add filters
        if ($this->get_prop('agent_id')) {
            $args['agent_id'] = $this->get_prop('agent_id');
        }
        
        if ($this->get_prop('property_type')) {
            $args['property_type'] = $this->get_prop('property_type');
        }
        
        if ($this->get_prop('price_min')) {
            $args['price_min'] = $this->get_prop('price_min');
        }
        
        if ($this->get_prop('price_max')) {
            $args['price_max'] = $this->get_prop('price_max');
        }
        
        if ($this->get_prop('bedrooms_min')) {
            $args['bedrooms_min'] = $this->get_prop('bedrooms_min');
        }
        
        if ($this->get_prop('bathrooms_min')) {
            $args['bathrooms_min'] = $this->get_prop('bathrooms_min');
        }
        
        if ($this->get_prop('city')) {
            $args['city'] = $this->get_prop('city');
        }
        
        if ($this->get_prop('state')) {
            $args['state'] = $this->get_prop('state');
        }
        
        if ($this->get_prop('zip')) {
            $args['zip'] = $this->get_prop('zip');
        }
        
        return hph_search_listings($args);
    }
    
    /**
     * Get total count of listings matching criteria
     */
    private function get_total_count() {
        // This would typically come from a separate count query
        // For now, estimate based on current listings
        $listings = $this->get_prop('listings');
        if (!empty($listings)) {
            return count($listings);
        }
        
        // Return count from search function if available
        return hph_count_listings($this->build_search_args());
    }
    
    /**
     * Build search arguments for counting
     */
    private function build_search_args() {
        $args = [];
        
        if ($this->get_prop('agent_id')) $args['agent_id'] = $this->get_prop('agent_id');
        if ($this->get_prop('property_type')) $args['property_type'] = $this->get_prop('property_type');
        if ($this->get_prop('status')) $args['status'] = $this->get_prop('status');
        if ($this->get_prop('price_min')) $args['price_min'] = $this->get_prop('price_min');
        if ($this->get_prop('price_max')) $args['price_max'] = $this->get_prop('price_max');
        if ($this->get_prop('bedrooms_min')) $args['bedrooms_min'] = $this->get_prop('bedrooms_min');
        if ($this->get_prop('bathrooms_min')) $args['bathrooms_min'] = $this->get_prop('bathrooms_min');
        if ($this->get_prop('city')) $args['city'] = $this->get_prop('city');
        if ($this->get_prop('state')) $args['state'] = $this->get_prop('state');
        if ($this->get_prop('zip')) $args['zip'] = $this->get_prop('zip');
        
        return $args;
    }
    
    /**
     * Get grid CSS classes
     */
    private function get_grid_classes() {
        $classes = [];
        
        $classes[] = 'hph-grid--columns-' . $this->get_prop('columns');
        $classes[] = 'hph-grid--mobile-' . $this->get_prop('columns_mobile');
        $classes[] = 'hph-grid--tablet-' . $this->get_prop('columns_tablet');
        $classes[] = 'hph-grid--' . $this->get_prop('card_style');
        $classes[] = 'hph-grid--hover-' . $this->get_prop('hover_effect');
        
        if ($this->get_prop('lazy_load')) {
            $classes[] = 'hph-grid--lazy-load';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Render individual listing card
     */
    private function render_listing_card($listing) {
        $card_style = $this->get_prop('card_style');
        $aspect_ratio = $this->get_prop('aspect_ratio');
        $enable_favorites = $this->get_prop('enable_favorites');
        $enable_quick_view = $this->get_prop('enable_quick_view');
        
        $listing_data = hph_get_template_listing_data($listing['id']);
        $formatted_price = hph_format_price($listing_data['price']);
        $listing_url = hph_get_listing_url($listing['id']);
        
        echo '<article class="hph-listing-card hph-card--' . esc_attr($card_style) . '" data-listing-id="' . esc_attr($listing['id']) . '">';
        
        // Card image
        echo '<div class="hph-card-image" style="aspect-ratio: ' . esc_attr($aspect_ratio) . '">';
        
        if (!empty($listing_data['featured_image'])) {
            $image_url = $this->get_prop('lazy_load') ? '' : $listing_data['featured_image'];
            $lazy_attrs = $this->get_prop('lazy_load') ? 'loading="lazy" data-src="' . esc_url($listing_data['featured_image']) . '"' : 'src="' . esc_url($listing_data['featured_image']) . '"';
            
            echo '<a href="' . esc_url($listing_url) . '" class="hph-card-image-link">';
            echo '<img ' . $lazy_attrs . ' alt="' . esc_attr($listing_data['title']) . '" class="hph-card-image-img">';
            echo '</a>';
        } else {
            echo '<a href="' . esc_url($listing_url) . '" class="hph-card-image-link hph-card-image--placeholder">';
            echo '<div class="hph-placeholder-content">';
            echo '<span class="hph-placeholder-text">' . esc_html__('No Image Available', 'happy-place') . '</span>';
            echo '</div>';
            echo '</a>';
        }
        
        // Status badge
        if (!empty($listing_data['status']) && $listing_data['status'] !== 'active') {
            echo '<div class="hph-card-status hph-status--' . esc_attr($listing_data['status']) . '">';
            echo esc_html(ucwords($listing_data['status']));
            echo '</div>';
        }
        
        // Favorite button
        if ($enable_favorites) {
            echo '<button type="button" class="hph-card-favorite" data-listing-id="' . esc_attr($listing['id']) . '" aria-label="' . esc_attr__('Add to favorites', 'happy-place') . '">';
            echo '<span class="hph-favorite-icon" aria-hidden="true"></span>';
            echo '</button>';
        }
        
        // Quick view button
        if ($enable_quick_view) {
            echo '<button type="button" class="hph-card-quick-view" data-listing-id="' . esc_attr($listing['id']) . '" aria-label="' . esc_attr__('Quick view', 'happy-place') . '">';
            echo '<span class="hph-quick-view-icon" aria-hidden="true"></span>';
            echo '</button>';
        }
        
        echo '</div>';
        
        // Card content
        echo '<div class="hph-card-content">';
        
        // Price
        echo '<div class="hph-card-price">' . esc_html($formatted_price) . '</div>';
        
        // Title
        echo '<h3 class="hph-card-title">';
        echo '<a href="' . esc_url($listing_url) . '" class="hph-card-title-link">';
        echo esc_html($listing_data['title']);
        echo '</a>';
        echo '</h3>';
        
        // Address
        if (!empty($listing_data['address'])) {
            echo '<div class="hph-card-address">' . esc_html($listing_data['address']) . '</div>';
        }
        
        // Features summary
        $features = [];
        if ($listing_data['bedrooms'] > 0) {
            $features[] = $listing_data['bedrooms'] . ' ' . _n('bed', 'beds', $listing_data['bedrooms'], 'happy-place');
        }
        if ($listing_data['bathrooms'] > 0) {
            $features[] = $listing_data['formatted_bathrooms'] . ' ' . _n('bath', 'baths', $listing_data['bathrooms'], 'happy-place');
        }
        if ($listing_data['square_feet'] > 0) {
            $features[] = $listing_data['formatted_sqft'] . ' sqft';
        }
        
        if (!empty($features)) {
            echo '<div class="hph-card-features">' . esc_html(implode(' • ', $features)) . '</div>';
        }
        
        // Agent info (if card style is featured)
        if ($card_style === 'featured' && !empty($listing_data['listing_agent'])) {
            $agent = $listing_data['listing_agent'];
            echo '<div class="hph-card-agent">';
            echo '<div class="hph-agent-info">';
            
            if (!empty($agent['profile_image'])) {
                echo '<img src="' . esc_url($agent['profile_image']) . '" alt="' . esc_attr($agent['display_name']) . '" class="hph-agent-avatar">';
            }
            
            echo '<div class="hph-agent-details">';
            echo '<div class="hph-agent-name">' . esc_html($agent['display_name']) . '</div>';
            if (!empty($agent['title'])) {
                echo '<div class="hph-agent-title">' . esc_html($agent['title']) . '</div>';
            }
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        // Days on market (if compact style)
        if ($card_style === 'compact' && !empty($listing_data['days_on_market'])) {
            echo '<div class="hph-card-dom">';
            echo esc_html(sprintf(__('%d days on market', 'happy-place'), $listing_data['days_on_market']));
            echo '</div>';
        }
        
        echo '</div>';
        
        echo '</article>';
    }
    
    /**
     * Render no results message
     */
    private function render_no_results() {
        ob_start();
        ?>
        <div class="hph-listing-grid hph-grid--no-results" data-component="listing-grid">
            <div class="hph-no-results">
                <div class="hph-no-results-icon" aria-hidden="true">🏠</div>
                <h3 class="hph-no-results-title"><?php esc_html_e('No Properties Found', 'happy-place'); ?></h3>
                <p class="hph-no-results-text">
                    <?php esc_html_e('We couldn\'t find any properties matching your criteria. Try adjusting your search filters.', 'happy-place'); ?>
                </p>
                <button type="button" class="hph-button hph-button--secondary hph-clear-filters">
                    <?php esc_html_e('Clear All Filters', 'happy-place'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render sorting controls
     */
    private function render_sorting_controls() {
        $current_sort = $this->get_prop('sort_by');
        
        $sort_options = [
            'price_desc' => __('Price: High to Low', 'happy-place'),
            'price_asc' => __('Price: Low to High', 'happy-place'),
            'newest' => __('Newest First', 'happy-place'),
            'sqft_desc' => __('Largest First', 'happy-place'),
            'bedrooms_desc' => __('Most Bedrooms', 'happy-place'),
            'days_asc' => __('Days on Market', 'happy-place')
        ];
        
        echo '<div class="hph-grid-sorting">';
        echo '<label for="hph-sort-select" class="hph-sort-label">' . esc_html__('Sort by:', 'happy-place') . '</label>';
        echo '<select id="hph-sort-select" class="hph-sort-select" data-action="sort-listings">';
        
        foreach ($sort_options as $value => $label) {
            $selected = $current_sort === $value ? 'selected' : '';
            echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '</div>';
    }
    
    /**
     * Render filter controls
     */
    private function render_filters() {
        echo '<div class="hph-grid-filters">';
        echo '<div class="hph-filters-container">';
        
        // Price range
        echo '<div class="hph-filter-group">';
        echo '<label class="hph-filter-label">' . esc_html__('Price Range', 'happy-place') . '</label>';
        echo '<div class="hph-filter-price-range">';
        echo '<input type="number" class="hph-filter-input" placeholder="Min Price" data-filter="price_min" value="' . esc_attr($this->get_prop('price_min')) . '">';
        echo '<span class="hph-filter-separator">-</span>';
        echo '<input type="number" class="hph-filter-input" placeholder="Max Price" data-filter="price_max" value="' . esc_attr($this->get_prop('price_max')) . '">';
        echo '</div>';
        echo '</div>';
        
        // Bedrooms
        echo '<div class="hph-filter-group">';
        echo '<label class="hph-filter-label">' . esc_html__('Bedrooms', 'happy-place') . '</label>';
        echo '<select class="hph-filter-select" data-filter="bedrooms_min">';
        echo '<option value="">' . esc_html__('Any', 'happy-place') . '</option>';
        for ($i = 1; $i <= 6; $i++) {
            $selected = $this->get_prop('bedrooms_min') == $i ? 'selected' : '';
            echo '<option value="' . $i . '" ' . $selected . '>' . $i . '+</option>';
        }
        echo '</select>';
        echo '</div>';
        
        // Bathrooms
        echo '<div class="hph-filter-group">';
        echo '<label class="hph-filter-label">' . esc_html__('Bathrooms', 'happy-place') . '</label>';
        echo '<select class="hph-filter-select" data-filter="bathrooms_min">';
        echo '<option value="">' . esc_html__('Any', 'happy-place') . '</option>';
        for ($i = 1; $i <= 5; $i++) {
            $selected = $this->get_prop('bathrooms_min') == $i ? 'selected' : '';
            $label = $i . ($i < 5 ? '+' : '');
            echo '<option value="' . $i . '" ' . $selected . '>' . $label . '</option>';
        }
        echo '</select>';
        echo '</div>';
        
        // Property type
        echo '<div class="hph-filter-group">';
        echo '<label class="hph-filter-label">' . esc_html__('Property Type', 'happy-place') . '</label>';
        echo '<select class="hph-filter-select" data-filter="property_type">';
        echo '<option value="">' . esc_html__('Any Type', 'happy-place') . '</option>';
        
        $property_types = hph_get_property_types();
        foreach ($property_types as $type => $label) {
            $selected = $this->get_prop('property_type') === $type ? 'selected' : '';
            echo '<option value="' . esc_attr($type) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        
        echo '<div class="hph-filter-actions">';
        echo '<button type="button" class="hph-button hph-button--primary hph-apply-filters">' . esc_html__('Apply Filters', 'happy-place') . '</button>';
        echo '<button type="button" class="hph-button hph-button--text hph-clear-filters">' . esc_html__('Clear', 'happy-place') . '</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render pagination
     */
    private function render_pagination($total_count) {
        $limit = $this->get_prop('limit');
        $current_page = $this->get_prop('page');
        $total_pages = ceil($total_count / $limit);
        
        if ($total_pages <= 1) {
            return;
        }
        
        echo '<nav class="hph-grid-pagination" aria-label="' . esc_attr__('Listings pagination', 'happy-place') . '">';
        echo '<ul class="hph-pagination-list">';
        
        // Previous page
        if ($current_page > 1) {
            echo '<li class="hph-pagination-item">';
            echo '<a href="#" class="hph-pagination-link" data-page="' . ($current_page - 1) . '" aria-label="' . esc_attr__('Previous page', 'happy-place') . '">';
            echo '<span aria-hidden="true">‹</span>';
            echo '</a>';
            echo '</li>';
        }
        
        // Page numbers
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1) {
            echo '<li class="hph-pagination-item">';
            echo '<a href="#" class="hph-pagination-link" data-page="1">1</a>';
            echo '</li>';
            if ($start_page > 2) {
                echo '<li class="hph-pagination-item hph-pagination-ellipsis">…</li>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $current_class = $i === $current_page ? 'hph-pagination-link--current' : '';
            echo '<li class="hph-pagination-item">';
            if ($i === $current_page) {
                echo '<span class="hph-pagination-link ' . $current_class . '" aria-current="page">' . $i . '</span>';
            } else {
                echo '<a href="#" class="hph-pagination-link" data-page="' . $i . '">' . $i . '</a>';
            }
            echo '</li>';
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<li class="hph-pagination-item hph-pagination-ellipsis">…</li>';
            }
            echo '<li class="hph-pagination-item">';
            echo '<a href="#" class="hph-pagination-link" data-page="' . $total_pages . '">' . $total_pages . '</a>';
            echo '</li>';
        }
        
        // Next page
        if ($current_page < $total_pages) {
            echo '<li class="hph-pagination-item">';
            echo '<a href="#" class="hph-pagination-link" data-page="' . ($current_page + 1) . '" aria-label="' . esc_attr__('Next page', 'happy-place') . '">';
            echo '<span aria-hidden="true">›</span>';
            echo '</a>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</nav>';
    }
    
    /**
     * Render load more button
     */
    private function render_load_more_button() {
        echo '<div class="hph-grid-load-more">';
        echo '<button type="button" class="hph-button hph-button--secondary hph-load-more-btn" data-action="load-more">';
        echo '<span class="hph-load-more-text">' . esc_html__('Load More Properties', 'happy-place') . '</span>';
        echo '<span class="hph-load-more-spinner" aria-hidden="true"></span>';
        echo '</button>';
        echo '</div>';
    }
    
    /**
     * Check if there are more listings to load
     */
    private function has_more_listings($total_count) {
        $limit = $this->get_prop('limit');
        $current_page = $this->get_prop('page');
        $loaded_count = $limit * $current_page;
        
        return $loaded_count < $total_count;
    }
}
