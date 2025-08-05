<?php
/**
 * Enhanced Filters Component
 *
 * Advanced filtering system for property searches with location, price,
 * property type, features, and map integration.
 *
 * @package HappyPlace\Components\UI
 * @since 2.0.0
 */

namespace HappyPlace\Components\UI;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Enhanced_Filters extends Base_Component {
    
    /**
     * Component name identifier
     *
     * @return string
     */
    protected function get_component_name() {
        return 'enhanced-filters';
    }
    
    /**
     * Default component properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            // Layout
            'layout' => 'horizontal', // horizontal, vertical, sidebar, modal
            'style' => 'standard', // standard, minimal, advanced, compact
            'collapsible' => true,
            'initially_collapsed' => false,
            
            // Filters to show
            'show_location' => true,
            'show_property_type' => true,
            'show_price_range' => true,
            'show_bedrooms' => true,
            'show_bathrooms' => true,
            'show_square_footage' => true,
            'show_features' => true,
            'show_status' => true,
            'show_sort' => true,
            'show_view_toggle' => true,
            
            // Advanced features
            'enable_map_search' => false,
            'enable_saved_searches' => false,
            'enable_quick_filters' => true,
            'enable_reset' => true,
            'enable_keyword_search' => true,
            
            // Behavior
            'auto_submit' => true,
            'submit_delay' => 500, // ms
            'update_url' => true,
            'persistent_filters' => true,
            
            // Defaults
            'default_sort' => 'newest',
            'default_view' => 'grid',
            'max_price' => 10000000,
            'price_step' => 50000,
            
            // Custom options
            'custom_features' => [],
            'location_hierarchy' => true, // Enable city > neighborhood filtering
            'price_presets' => [
                'Under $500K' => ['min' => 0, 'max' => 500000],
                '$500K - $1M' => ['min' => 500000, 'max' => 1000000],
                '$1M - $2M' => ['min' => 1000000, 'max' => 2000000],
                'Over $2M' => ['min' => 2000000, 'max' => null]
            ]
        ];
    }
    
    /**
     * Validate component properties
     */
    protected function validate_props() {
        $valid_layouts = ['horizontal', 'vertical', 'sidebar', 'modal'];
        if (!in_array($this->get_prop('layout'), $valid_layouts)) {
            $this->add_validation_error('Invalid layout. Must be: ' . implode(', ', $valid_layouts));
        }
        
        $valid_styles = ['standard', 'minimal', 'advanced', 'compact'];
        if (!in_array($this->get_prop('style'), $valid_styles)) {
            $this->add_validation_error('Invalid style. Must be: ' . implode(', ', $valid_styles));
        }
    }
    
    /**
     * Render the component
     *
     * @return string
     */
    protected function render() {
        $classes = $this->build_css_classes();
        $current_filters = $this->get_current_filters();
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes); ?>" 
             data-component="enhanced-filters"
             data-auto-submit="<?php echo $this->get_prop('auto_submit') ? 'true' : 'false'; ?>"
             data-submit-delay="<?php echo esc_attr($this->get_prop('submit_delay')); ?>"
             data-update-url="<?php echo $this->get_prop('update_url') ? 'true' : 'false'; ?>">
            
            <!-- Filter Form -->
            <form class="hph-filters__form" method="get" action="<?php echo esc_url($this->get_search_url()); ?>">
                
                <!-- Header -->
                <?php if ($this->get_prop('layout') !== 'minimal'): ?>
                    <div class="hph-filters__header">
                        <h3 class="hph-filters__title"><?php esc_html_e('Filter Properties', 'happy-place'); ?></h3>
                        
                        <!-- Quick Filters -->
                        <?php if ($this->get_prop('enable_quick_filters')): ?>
                            <div class="hph-filters__quick">
                                <?php $this->render_quick_filters($current_filters); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Toggle (for collapsible) -->
                        <?php if ($this->get_prop('collapsible')): ?>
                            <button type="button" 
                                    class="hph-filters__toggle"
                                    aria-expanded="<?php echo $this->get_prop('initially_collapsed') ? 'false' : 'true'; ?>"
                                    aria-controls="hph-filters-content">
                                <span class="hph-filters__toggle-text">
                                    <?php esc_html_e('Filters', 'happy-place'); ?>
                                </span>
                                <i class="hph-icon hph-icon--chevron-down" aria-hidden="true"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filter Content -->
                <div class="hph-filters__content" 
                     id="hph-filters-content"
                     <?php if ($this->get_prop('collapsible') && $this->get_prop('initially_collapsed')): ?>
                         style="display: none;"
                     <?php endif; ?>>
                    
                    <!-- Keyword Search -->
                    <?php if ($this->get_prop('enable_keyword_search')): ?>
                        <div class="hph-filters__group hph-filters__group--keyword">
                            <?php $this->render_keyword_search($current_filters); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Main Filters -->
                    <div class="hph-filters__main">
                        
                        <!-- Location -->
                        <?php if ($this->get_prop('show_location')): ?>
                            <div class="hph-filters__group hph-filters__group--location">
                                <?php $this->render_location_filter($current_filters); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Property Type -->
                        <?php if ($this->get_prop('show_property_type')): ?>
                            <div class="hph-filters__group hph-filters__group--type">
                                <?php $this->render_property_type_filter($current_filters); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Status -->
                        <?php if ($this->get_prop('show_status')): ?>
                            <div class="hph-filters__group hph-filters__group--status">
                                <?php $this->render_status_filter($current_filters); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Price Range -->
                        <?php if ($this->get_prop('show_price_range')): ?>
                            <div class="hph-filters__group hph-filters__group--price">
                                <?php $this->render_price_filter($current_filters); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Bedrooms & Bathrooms -->
                        <div class="hph-filters__row">
                            <?php if ($this->get_prop('show_bedrooms')): ?>
                                <div class="hph-filters__group hph-filters__group--bedrooms">
                                    <?php $this->render_bedrooms_filter($current_filters); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($this->get_prop('show_bathrooms')): ?>
                                <div class="hph-filters__group hph-filters__group--bathrooms">
                                    <?php $this->render_bathrooms_filter($current_filters); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Square Footage -->
                        <?php if ($this->get_prop('show_square_footage')): ?>
                            <div class="hph-filters__group hph-filters__group--sqft">
                                <?php $this->render_sqft_filter($current_filters); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Features -->
                        <?php if ($this->get_prop('show_features')): ?>
                            <div class="hph-filters__group hph-filters__group--features">
                                <?php $this->render_features_filter($current_filters); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Sort & View -->
                        <div class="hph-filters__row hph-filters__row--controls">
                            <?php if ($this->get_prop('show_sort')): ?>
                                <div class="hph-filters__group hph-filters__group--sort">
                                    <?php $this->render_sort_filter($current_filters); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($this->get_prop('show_view_toggle')): ?>
                                <div class="hph-filters__group hph-filters__group--view">
                                    <?php $this->render_view_toggle($current_filters); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                    
                    <!-- Actions -->
                    <div class="hph-filters__actions">
                        <?php if (!$this->get_prop('auto_submit')): ?>
                            <button type="submit" class="hph-btn hph-btn--primary">
                                <?php esc_html_e('Apply Filters', 'happy-place'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($this->get_prop('enable_reset')): ?>
                            <button type="button" 
                                    class="hph-btn hph-btn--secondary hph-filters__reset">
                                <?php esc_html_e('Reset', 'happy-place'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($this->get_prop('enable_saved_searches')): ?>
                            <button type="button" 
                                    class="hph-btn hph-btn--outline hph-filters__save"
                                    data-action="save-search">
                                <i class="hph-icon hph-icon--bookmark" aria-hidden="true"></i>
                                <?php esc_html_e('Save Search', 'happy-place'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                </div>
                
                <!-- Hidden fields for maintaining state -->
                <?php $this->render_hidden_fields($current_filters); ?>
                
            </form>
            
            <!-- Map Search (if enabled) -->
            <?php if ($this->get_prop('enable_map_search')): ?>
                <div class="hph-filters__map-search">
                    <?php $this->render_map_search(); ?>
                </div>
            <?php endif; ?>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Build CSS classes
     */
    private function build_css_classes() {
        $classes = ['hph-enhanced-filters'];
        
        $classes[] = 'hph-filters--' . $this->get_prop('layout');
        $classes[] = 'hph-filters--' . $this->get_prop('style');
        
        if ($this->get_prop('collapsible')) {
            $classes[] = 'hph-filters--collapsible';
        }
        
        if ($this->get_prop('enable_map_search')) {
            $classes[] = 'hph-filters--with-map';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Get current filter values from request
     */
    private function get_current_filters() {
        return [
            'keyword' => sanitize_text_field($_GET['keyword'] ?? ''),
            'location' => sanitize_text_field($_GET['location'] ?? ''),
            'property_type' => sanitize_text_field($_GET['property_type'] ?? ''),
            'status' => sanitize_text_field($_GET['status'] ?? ''),
            'min_price' => absint($_GET['min_price'] ?? 0),
            'max_price' => absint($_GET['max_price'] ?? 0),
            'bedrooms' => absint($_GET['bedrooms'] ?? 0),
            'bathrooms' => absint($_GET['bathrooms'] ?? 0),
            'min_sqft' => absint($_GET['min_sqft'] ?? 0),
            'max_sqft' => absint($_GET['max_sqft'] ?? 0),
            'features' => array_map('sanitize_text_field', (array)($_GET['features'] ?? [])),
            'sort' => sanitize_text_field($_GET['sort'] ?? $this->get_prop('default_sort')),
            'view' => sanitize_text_field($_GET['view'] ?? $this->get_prop('default_view'))
        ];
    }
    
    /**
     * Get search URL
     */
    private function get_search_url() {
        return home_url('/properties/');
    }
    
    /**
     * Render quick filters
     */
    private function render_quick_filters($current_filters) {
        $quick_filters = [
            'For Sale' => ['status' => 'for-sale'],
            'For Rent' => ['status' => 'for-rent'],
            'Under $500K' => ['max_price' => 500000],
            '3+ Beds' => ['bedrooms' => 3],
            'New Listings' => ['sort' => 'newest']
        ];
        
        echo '<div class="hph-quick-filters">';
        foreach ($quick_filters as $label => $filters) {
            $is_active = $this->is_quick_filter_active($filters, $current_filters);
            $class = $is_active ? 'hph-quick-filter hph-quick-filter--active' : 'hph-quick-filter';
            
            echo '<button type="button" class="' . esc_attr($class) . '" data-filters="' . esc_attr(json_encode($filters)) . '">';
            echo esc_html($label);
            echo '</button>';
        }
        echo '</div>';
    }
    
    /**
     * Check if quick filter is active
     */
    private function is_quick_filter_active($filters, $current_filters) {
        foreach ($filters as $key => $value) {
            if (($current_filters[$key] ?? '') != $value) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Render keyword search
     */
    private function render_keyword_search($current_filters) {
        ?>
        <label class="hph-filters__label">
            <?php esc_html_e('Keyword Search', 'happy-place'); ?>
        </label>
        <input type="text" 
               name="keyword" 
               value="<?php echo esc_attr($current_filters['keyword']); ?>"
               placeholder="<?php esc_attr_e('Enter location, address, or MLS#...', 'happy-place'); ?>"
               class="hph-filters__input hph-filters__input--keyword">
        <?php
    }
    
    /**
     * Render location filter
     */
    private function render_location_filter($current_filters) {
        ?>
        <label class="hph-filters__label">
            <?php esc_html_e('Location', 'happy-place'); ?>
        </label>
        <select name="location" class="hph-filters__select hph-filters__select--location">
            <option value=""><?php esc_html_e('All Locations', 'happy-place'); ?></option>
            <?php
            // TODO: Get locations from bridge function when available
            $locations = [
                'downtown' => 'Downtown',
                'midtown' => 'Midtown', 
                'uptown' => 'Uptown',
                'suburbs' => 'Suburbs'
            ];
            
            foreach ($locations as $value => $label) {
                $selected = selected($current_filters['location'], $value, false);
                echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
            }
            ?>
        </select>
        <?php
    }
    
    /**
     * Render property type filter
     */
    private function render_property_type_filter($current_filters) {
        ?>
        <label class="hph-filters__label">
            <?php esc_html_e('Property Type', 'happy-place'); ?>
        </label>
        <select name="property_type" class="hph-filters__select hph-filters__select--type">
            <option value=""><?php esc_html_e('All Types', 'happy-place'); ?></option>
            <?php
            $property_types = [
                'single-family' => __('Single Family', 'happy-place'),
                'condo' => __('Condo', 'happy-place'),
                'townhouse' => __('Townhouse', 'happy-place'),
                'multi-family' => __('Multi-Family', 'happy-place'),
                'land' => __('Land', 'happy-place'),
                'commercial' => __('Commercial', 'happy-place')
            ];
            
            foreach ($property_types as $value => $label) {
                $selected = selected($current_filters['property_type'], $value, false);
                echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
            }
            ?>
        </select>
        <?php
    }
    
    /**
     * Render status filter
     */
    private function render_status_filter($current_filters) {
        ?>
        <label class="hph-filters__label">
            <?php esc_html_e('Status', 'happy-place'); ?>
        </label>
        <select name="status" class="hph-filters__select hph-filters__select--status">
            <option value=""><?php esc_html_e('All Status', 'happy-place'); ?></option>
            <option value="for-sale" <?php selected($current_filters['status'], 'for-sale'); ?>><?php esc_html_e('For Sale', 'happy-place'); ?></option>
            <option value="for-rent" <?php selected($current_filters['status'], 'for-rent'); ?>><?php esc_html_e('For Rent', 'happy-place'); ?></option>
            <option value="sold" <?php selected($current_filters['status'], 'sold'); ?>><?php esc_html_e('Sold', 'happy-place'); ?></option>
            <option value="pending" <?php selected($current_filters['status'], 'pending'); ?>><?php esc_html_e('Pending', 'happy-place'); ?></option>
        </select>
        <?php
    }
    
    /**
     * Render price filter
     */
    private function render_price_filter($current_filters) {
        ?>
        <label class="hph-filters__label">
            <?php esc_html_e('Price Range', 'happy-place'); ?>
        </label>
        
        <!-- Price Presets -->
        <div class="hph-filters__price-presets">
            <?php foreach ($this->get_prop('price_presets') as $label => $range): ?>
                <button type="button" 
                        class="hph-price-preset" 
                        data-min="<?php echo esc_attr($range['min']); ?>" 
                        data-max="<?php echo esc_attr($range['max'] ?? ''); ?>">
                    <?php echo esc_html($label); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Custom Range -->
        <div class="hph-filters__price-range">
            <div class="hph-filters__price-inputs">
                <input type="number" 
                       name="min_price" 
                       value="<?php echo esc_attr($current_filters['min_price'] ?: ''); ?>"
                       placeholder="<?php esc_attr_e('Min Price', 'happy-place'); ?>"
                       step="<?php echo esc_attr($this->get_prop('price_step')); ?>"
                       class="hph-filters__input hph-filters__input--price">
                <span class="hph-filters__separator">to</span>
                <input type="number" 
                       name="max_price" 
                       value="<?php echo esc_attr($current_filters['max_price'] ?: ''); ?>"
                       placeholder="<?php esc_attr_e('Max Price', 'happy-place'); ?>"
                       step="<?php echo esc_attr($this->get_prop('price_step')); ?>"
                       class="hph-filters__input hph-filters__input--price">
            </div>
        </div>
        <?php
    }
    
    /**
     * Render bedrooms filter
     */
    private function render_bedrooms_filter($current_filters) {
        ?>
        <label class="hph-filters__label">
            <?php esc_html_e('Bedrooms', 'happy-place'); ?>
        </label>
        <select name="bedrooms" class="hph-filters__select hph-filters__select--bedrooms">
            <option value=""><?php esc_html_e('Any', 'happy-place'); ?></option>
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <option value="<?php echo $i; ?>" <?php selected($current_filters['bedrooms'], $i); ?>>
                    <?php echo $i; ?>+ <?php esc_html_e('Beds', 'happy-place'); ?>
                </option>
            <?php endfor; ?>
        </select>
        <?php
    }
    
    /**
     * Render bathrooms filter
     */
    private function render_bathrooms_filter($current_filters) {
        ?>
        <label class="hph-filters__label">
            <?php esc_html_e('Bathrooms', 'happy-place'); ?>
        </label>
        <select name="bathrooms" class="hph-filters__select hph-filters__select--bathrooms">
            <option value=""><?php esc_html_e('Any', 'happy-place'); ?></option>
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>" <?php selected($current_filters['bathrooms'], $i); ?>>
                    <?php echo $i; ?>+ <?php esc_html_e('Baths', 'happy-place'); ?>
                </option>
            <?php endfor; ?>
        </select>
        <?php
    }
    
    /**
     * Render square footage filter
     */
    private function render_sqft_filter($current_filters) {
        ?>
        <label class="hph-filters__label">
            <?php esc_html_e('Square Footage', 'happy-place'); ?>
        </label>
        <div class="hph-filters__sqft-range">
            <input type="number" 
                   name="min_sqft" 
                   value="<?php echo esc_attr($current_filters['min_sqft'] ?: ''); ?>"
                   placeholder="<?php esc_attr_e('Min Sq Ft', 'happy-place'); ?>"
                   step="100"
                   class="hph-filters__input hph-filters__input--sqft">
            <span class="hph-filters__separator">to</span>
            <input type="number" 
                   name="max_sqft" 
                   value="<?php echo esc_attr($current_filters['max_sqft'] ?: ''); ?>"
                   placeholder="<?php esc_attr_e('Max Sq Ft', 'happy-place'); ?>"
                   step="100"
                   class="hph-filters__input hph-filters__input--sqft">
        </div>
        <?php
    }
    
    /**
     * Render features filter
     */
    private function render_features_filter($current_filters) {
        ?>
        <label class="hph-filters__label">
            <?php esc_html_e('Features', 'happy-place'); ?>
        </label>
        <div class="hph-filters__features">
            <?php
            $features = array_merge([
                'pool' => __('Pool', 'happy-place'),
                'garage' => __('Garage', 'happy-place'),
                'fireplace' => __('Fireplace', 'happy-place'),
                'air-conditioning' => __('A/C', 'happy-place'),
                'hardwood-floors' => __('Hardwood Floors', 'happy-place'),
                'basement' => __('Basement', 'happy-place'),
                'waterfront' => __('Waterfront', 'happy-place'),
                'new-construction' => __('New Construction', 'happy-place')
            ], $this->get_prop('custom_features'));
            
            foreach ($features as $value => $label) {
                $checked = in_array($value, $current_filters['features']) ? 'checked' : '';
                ?>
                <label class="hph-filters__checkbox">
                    <input type="checkbox" 
                           name="features[]" 
                           value="<?php echo esc_attr($value); ?>" 
                           <?php echo $checked; ?>
                           class="hph-filters__checkbox-input">
                    <span class="hph-filters__checkbox-label"><?php echo esc_html($label); ?></span>
                </label>
                <?php
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Render sort filter
     */
    private function render_sort_filter($current_filters) {
        ?>
        <label class="hph-filters__label">
            <?php esc_html_e('Sort By', 'happy-place'); ?>
        </label>
        <select name="sort" class="hph-filters__select hph-filters__select--sort">
            <option value="newest" <?php selected($current_filters['sort'], 'newest'); ?>><?php esc_html_e('Newest First', 'happy-place'); ?></option>
            <option value="price-low" <?php selected($current_filters['sort'], 'price-low'); ?>><?php esc_html_e('Price: Low to High', 'happy-place'); ?></option>
            <option value="price-high" <?php selected($current_filters['sort'], 'price-high'); ?>><?php esc_html_e('Price: High to Low', 'happy-place'); ?></option>
            <option value="sqft-large" <?php selected($current_filters['sort'], 'sqft-large'); ?>><?php esc_html_e('Largest First', 'happy-place'); ?></option>
            <option value="bedrooms" <?php selected($current_filters['sort'], 'bedrooms'); ?>><?php esc_html_e('Most Bedrooms', 'happy-place'); ?></option>
        </select>
        <?php
    }
    
    /**
     * Render view toggle
     */
    private function render_view_toggle($current_filters) {
        ?>
        <label class="hph-filters__label">
            <?php esc_html_e('View', 'happy-place'); ?>
        </label>
        <div class="hph-filters__view-toggle">
            <button type="button" 
                    class="hph-view-toggle hph-view-toggle--grid <?php echo $current_filters['view'] === 'grid' ? 'hph-view-toggle--active' : ''; ?>"
                    data-view="grid">
                <i class="hph-icon hph-icon--grid" aria-hidden="true"></i>
                <span class="hph-sr-only"><?php esc_html_e('Grid View', 'happy-place'); ?></span>
            </button>
            <button type="button" 
                    class="hph-view-toggle hph-view-toggle--list <?php echo $current_filters['view'] === 'list' ? 'hph-view-toggle--active' : ''; ?>"
                    data-view="list">
                <i class="hph-icon hph-icon--list" aria-hidden="true"></i>
                <span class="hph-sr-only"><?php esc_html_e('List View', 'happy-place'); ?></span>
            </button>
            <button type="button" 
                    class="hph-view-toggle hph-view-toggle--map <?php echo $current_filters['view'] === 'map' ? 'hph-view-toggle--active' : ''; ?>"
                    data-view="map">
                <i class="hph-icon hph-icon--map" aria-hidden="true"></i>
                <span class="hph-sr-only"><?php esc_html_e('Map View', 'happy-place'); ?></span>
            </button>
        </div>
        
        <!-- Hidden input to store current view -->
        <input type="hidden" name="view" value="<?php echo esc_attr($current_filters['view']); ?>" class="hph-filters__view-input">
        <?php
    }
    
    /**
     * Render map search
     */
    private function render_map_search() {
        ?>
        <div class="hph-filters__map-container">
            <div class="hph-filters__map" id="hph-filters-map"></div>
            <div class="hph-filters__map-controls">
                <button type="button" class="hph-btn hph-btn--primary hph-filters__search-area">
                    <?php esc_html_e('Search This Area', 'happy-place'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render hidden fields
     */
    private function render_hidden_fields($current_filters) {
        // Preserve any additional query parameters
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['keyword', 'location', 'property_type', 'status', 'min_price', 'max_price', 'bedrooms', 'bathrooms', 'min_sqft', 'max_sqft', 'features', 'sort', 'view'])) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($v) . '">';
                    }
                } else {
                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }
        }
    }
}
