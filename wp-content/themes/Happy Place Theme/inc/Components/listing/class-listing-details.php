<?php
/**
 * Listing Details Component
 *
 * Detailed property information section for single listing pages.
 *
 * @package HappyPlace\Components\Listing
 * @since 2.0.0
 */

namespace HappyPlace\Components\Listing;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Listing_Details extends Base_Component {
    
    /**
     * Component name identifier
     *
     * @return string
     */
    protected function get_component_name() {
        return 'listing-details';
    }
    
    /**
     * Default component properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            'listing_id' => 0,
            'show_description' => true,
            'show_features' => true,
            'show_specifications' => true,
            'show_location' => true,
            'show_financial' => false,
            'show_market_analytics' => false,
            'layout' => 'tabs', // tabs, sections, accordion
            'compact' => false,
            'sections' => [
                'description',
                'features', 
                'specifications',
                'location',
                'financial',
                'market'
            ]
        ];
    }
    
    /**
     * Validate component properties
     */
    protected function validate_props() {
        if (empty($this->get_prop('listing_id'))) {
            $this->add_validation_error('listing_id is required');
        }
    }
    
    /**
     * Render the component
     *
     * @return string
     */
    protected function render() {
        $listing_id = $this->get_prop('listing_id');
        
        // Get comprehensive listing data
        $listing_data = \hph_bridge_get_listing_data($listing_id);
        $description = $listing_data['description'] ?? '';
        $features = \hph_bridge_get_features($listing_id);
        $specifications = \hph_fallback_get_property_details($listing_id);
        $location = $listing_data; // Use main data for location info
        $financial = $this->get_prop('show_financial') ? \hph_fallback_get_financial_data($listing_id) : null;
        $market = $this->get_prop('show_market_analytics') ? [] : null; // No market function available
        
        $layout = $this->get_prop('layout');
        $compact_class = $this->get_prop('compact') ? 'hph-listing-details--compact' : '';
        
        ob_start();
        ?>
        <div class="hph-listing-details hph-listing-details--<?php echo esc_attr($layout); ?> <?php echo esc_attr($compact_class); ?>" 
             data-component="listing-details"
             data-listing-id="<?php echo esc_attr($listing_id); ?>">
            
            <?php if ($layout === 'tabs'): ?>
                <?php $this->render_tabs_layout($description, $features, $specifications, $location, $financial, $market); ?>
            <?php elseif ($layout === 'accordion'): ?>
                <?php $this->render_accordion_layout($description, $features, $specifications, $location, $financial, $market); ?>
            <?php else: ?>
                <?php $this->render_sections_layout($description, $features, $specifications, $location, $financial, $market); ?>
            <?php endif; ?>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render tabs layout
     */
    private function render_tabs_layout($description, $features, $specifications, $location, $financial, $market) {
        $sections = $this->get_active_sections();
        
        echo '<div class="hph-details-tabs">';
        
        // Tab navigation
        echo '<nav class="hph-details-tabs__nav" role="tablist">';
        $first = true;
        foreach ($sections as $section) {
            $active_class = $first ? 'hph-tab--active' : '';
            echo '<button type="button" class="hph-tab ' . esc_attr($active_class) . '" ';
            echo 'role="tab" aria-controls="hph-tab-' . esc_attr($section) . '" ';
            echo 'data-tab="' . esc_attr($section) . '">';
            echo esc_html($this->get_section_title($section));
            echo '</button>';
            $first = false;
        }
        echo '</nav>';
        
        // Tab content
        echo '<div class="hph-details-tabs__content">';
        $first = true;
        foreach ($sections as $section) {
            $active_class = $first ? 'hph-tab-content--active' : '';
            echo '<div id="hph-tab-' . esc_attr($section) . '" class="hph-tab-content ' . esc_attr($active_class) . '" role="tabpanel">';
            $this->render_section_content($section, $description, $features, $specifications, $location, $financial, $market);
            echo '</div>';
            $first = false;
        }
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Render accordion layout
     */
    private function render_accordion_layout($description, $features, $specifications, $location, $financial, $market) {
        $sections = $this->get_active_sections();
        
        echo '<div class="hph-details-accordion">';
        
        $first = true;
        foreach ($sections as $section) {
            $open_class = $first ? 'hph-accordion-item--open' : '';
            
            echo '<div class="hph-accordion-item ' . esc_attr($open_class) . '">';
            echo '<button type="button" class="hph-accordion-header" data-toggle="accordion" data-section="' . esc_attr($section) . '">';
            echo '<span class="hph-accordion-title">' . esc_html($this->get_section_title($section)) . '</span>';
            echo '<span class="hph-accordion-icon" aria-hidden="true"></span>';
            echo '</button>';
            
            echo '<div class="hph-accordion-content">';
            $this->render_section_content($section, $description, $features, $specifications, $location, $financial, $market);
            echo '</div>';
            echo '</div>';
            
            $first = false;
        }
        
        echo '</div>';
    }
    
    /**
     * Render sections layout
     */
    private function render_sections_layout($description, $features, $specifications, $location, $financial, $market) {
        $sections = $this->get_active_sections();
        
        echo '<div class="hph-details-sections">';
        
        foreach ($sections as $section) {
            echo '<section class="hph-details-section hph-details-section--' . esc_attr($section) . '">';
            echo '<h3 class="hph-section-title">' . esc_html($this->get_section_title($section)) . '</h3>';
            echo '<div class="hph-section-content">';
            $this->render_section_content($section, $description, $features, $specifications, $location, $financial, $market);
            echo '</div>';
            echo '</section>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render individual section content
     */
    private function render_section_content($section, $description, $features, $specifications, $location, $financial, $market) {
        switch ($section) {
            case 'description':
                $this->render_description_section($description);
                break;
            case 'features':
                $this->render_features_section($features);
                break;
            case 'specifications':
                $this->render_specifications_section($specifications);
                break;
            case 'location':
                $this->render_location_section($location);
                break;
            case 'financial':
                if ($financial) {
                    $this->render_financial_section($financial);
                }
                break;
            case 'market':
                if ($market) {
                    $this->render_market_section($market);
                }
                break;
        }
    }
    
    /**
     * Render description section
     */
    private function render_description_section($description) {
        if (empty($description)) {
            echo '<p class="hph-no-content">' . esc_html__('No description available.', 'happy-place') . '</p>';
            return;
        }
        
        echo '<div class="hph-property-description">';
        echo wp_kses_post($description);
        echo '</div>';
    }
    
    /**
     * Render features section
     */
    private function render_features_section($features) {
        if (empty($features)) {
            return;
        }
        
        echo '<div class="hph-features-grid">';
        
        // Core features
        if ($features['bedrooms'] > 0) {
            echo '<div class="hph-feature-item">';
            echo '<span class="hph-feature-label">' . esc_html__('Bedrooms', 'happy-place') . '</span>';
            echo '<span class="hph-feature-value">' . esc_html($features['bedrooms']) . '</span>';
            echo '</div>';
        }
        
        if ($features['bathrooms'] > 0) {
            echo '<div class="hph-feature-item">';
            echo '<span class="hph-feature-label">' . esc_html__('Bathrooms', 'happy-place') . '</span>';
            echo '<span class="hph-feature-value">' . esc_html($features['formatted_bathrooms']) . '</span>';
            echo '</div>';
        }
        
        if ($features['square_feet'] > 0) {
            echo '<div class="hph-feature-item">';
            echo '<span class="hph-feature-label">' . esc_html__('Square Feet', 'happy-place') . '</span>';
            echo '<span class="hph-feature-value">' . esc_html($features['formatted_sqft']) . '</span>';
            echo '</div>';
        }
        
        if ($features['lot_size'] > 0) {
            echo '<div class="hph-feature-item">';
            echo '<span class="hph-feature-label">' . esc_html__('Lot Size', 'happy-place') . '</span>';
            echo '<span class="hph-feature-value">' . esc_html($features['lot_size']) . ' acres</span>';
            echo '</div>';
        }
        
        if ($features['year_built'] > 0) {
            echo '<div class="hph-feature-item">';
            echo '<span class="hph-feature-label">' . esc_html__('Year Built', 'happy-place') . '</span>';
            echo '<span class="hph-feature-value">' . esc_html($features['year_built']) . '</span>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render specifications section
     */
    private function render_specifications_section($specifications) {
        if (empty($specifications)) {
            return;
        }
        
        echo '<div class="hph-specifications-table">';
        
        $specs_to_show = [
            'property_type' => __('Property Type', 'happy-place'),
            'property_style' => __('Style', 'happy-place'),
            'property_condition' => __('Condition', 'happy-place'),
            'stories' => __('Stories', 'happy-place'),
            'rooms_total' => __('Total Rooms', 'happy-place'),
            'parking_spaces' => __('Parking Spaces', 'happy-place'),
            'garage_spaces' => __('Garage Spaces', 'happy-place'),
            'basement' => __('Basement', 'happy-place'),
            'fireplace_count' => __('Fireplaces', 'happy-place')
        ];
        
        foreach ($specs_to_show as $key => $label) {
            $value = $specifications[$key] ?? '';
            if (!empty($value)) {
                echo '<div class="hph-spec-item">';
                echo '<span class="hph-spec-label">' . esc_html($label) . '</span>';
                echo '<span class="hph-spec-value">' . esc_html($value) . '</span>';
                echo '</div>';
            }
        }
        
        // Special features
        if (!empty($specifications['has_pool'])) {
            echo '<div class="hph-spec-item">';
            echo '<span class="hph-spec-label">' . esc_html__('Pool', 'happy-place') . '</span>';
            echo '<span class="hph-spec-value">' . esc_html__('Yes', 'happy-place') . '</span>';
            echo '</div>';
        }
        
        if (!empty($specifications['has_hot_tub_spa'])) {
            echo '<div class="hph-spec-item">';
            echo '<span class="hph-spec-label">' . esc_html__('Hot Tub/Spa', 'happy-place') . '</span>';
            echo '<span class="hph-spec-value">' . esc_html__('Yes', 'happy-place') . '</span>';
            echo '</div>';
        }
        
        if (!empty($specifications['is_waterfront'])) {
            echo '<div class="hph-spec-item">';
            echo '<span class="hph-spec-label">' . esc_html__('Waterfront', 'happy-place') . '</span>';
            echo '<span class="hph-spec-value">' . esc_html__('Yes', 'happy-place') . '</span>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render location section
     */
    private function render_location_section($location) {
        if (empty($location)) {
            return;
        }
        
        echo '<div class="hph-location-details">';
        
        // Address information
        if (!empty($location['street_address'])) {
            echo '<div class="hph-location-section">';
            echo '<h4 class="hph-location-subtitle">' . esc_html__('Address', 'happy-place') . '</h4>';
            echo '<div class="hph-address-details">';
            
            $listing_id = $this->get_prop('listing_id');
            $address = \hph_get_listing_address($listing_id, true);
            echo '<p class="hph-full-address">' . esc_html($address) . '</p>';
            
            if (!empty($location['county'])) {
                echo '<p class="hph-county">' . esc_html__('County:', 'happy-place') . ' ' . esc_html($location['county']) . '</p>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        // Neighborhood information
        if (!empty($location['neighborhood']) || !empty($location['school_district'])) {
            echo '<div class="hph-location-section">';
            echo '<h4 class="hph-location-subtitle">' . esc_html__('Neighborhood', 'happy-place') . '</h4>';
            echo '<div class="hph-neighborhood-details">';
            
            if (!empty($location['neighborhood'])) {
                echo '<p class="hph-neighborhood">' . esc_html__('Neighborhood:', 'happy-place') . ' ' . esc_html($location['neighborhood']) . '</p>';
            }
            
            if (!empty($location['school_district'])) {
                echo '<p class="hph-school-district">' . esc_html__('School District:', 'happy-place') . ' ' . esc_html($location['school_district']) . '</p>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        // Scores and ratings
        if (!empty($location['walkability_score']) || !empty($location['transit_score'])) {
            echo '<div class="hph-location-section">';
            echo '<h4 class="hph-location-subtitle">' . esc_html__('Walkability & Transit', 'happy-place') . '</h4>';
            echo '<div class="hph-scores-grid">';
            
            if (!empty($location['walkability_score'])) {
                echo '<div class="hph-score-item">';
                echo '<span class="hph-score-label">' . esc_html__('Walk Score', 'happy-place') . '</span>';
                echo '<span class="hph-score-value">' . esc_html($location['walkability_score']) . '</span>';
                echo '</div>';
            }
            
            if (!empty($location['transit_score'])) {
                echo '<div class="hph-score-item">';
                echo '<span class="hph-score-label">' . esc_html__('Transit Score', 'happy-place') . '</span>';
                echo '<span class="hph-score-value">' . esc_html($location['transit_score']) . '</span>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        // Additional location details
        $additional_details = array_filter([
            'mls_area_code' => $location['mls_area_code'] ?? '',
            'zoning' => $location['zoning'] ?? '',
            'flood_zone' => $location['flood_zone'] ?? '',
            'hoa_name' => $location['hoa_name'] ?? ''
        ]);
        
        if (!empty($additional_details)) {
            echo '<div class="hph-location-section">';
            echo '<h4 class="hph-location-subtitle">' . esc_html__('Additional Details', 'happy-place') . '</h4>';
            echo '<div class="hph-additional-details">';
            
            foreach ($additional_details as $key => $value) {
                if (!empty($value)) {
                    $label = ucwords(str_replace('_', ' ', $key));
                    echo '<p class="hph-detail-item">';
                    echo '<span class="hph-detail-label">' . esc_html($label) . ':</span> ';
                    echo '<span class="hph-detail-value">' . esc_html($value) . '</span>';
                    echo '</p>';
                }
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render financial section
     */
    private function render_financial_section($financial) {
        if (empty($financial)) {
            return;
        }
        
        echo '<div class="hph-financial-details">';
        
        // Tax information
        if (!empty($financial['taxes'])) {
            $taxes = $financial['taxes'];
            echo '<div class="hph-financial-section">';
            echo '<h4 class="hph-financial-subtitle">' . esc_html__('Taxes & Fees', 'happy-place') . '</h4>';
            echo '<div class="hph-financial-grid">';
            
            if (!empty($taxes['property_tax_annual'])) {
                echo '<div class="hph-financial-item">';
                echo '<span class="hph-financial-label">' . esc_html__('Annual Property Tax', 'happy-place') . '</span>';
                echo '<span class="hph-financial-value">' . \hph_format_price($taxes['property_tax_annual']) . '</span>';
                echo '</div>';
            }
            
            if (!empty($taxes['hoa_fee_monthly'])) {
                echo '<div class="hph-financial-item">';
                echo '<span class="hph-financial-label">' . esc_html__('Monthly HOA', 'happy-place') . '</span>';
                echo '<span class="hph-financial-value">' . \hph_format_price($taxes['hoa_fee_monthly']) . '</span>';
                echo '</div>';
            }
            
            if (!empty($taxes['insurance_annual'])) {
                echo '<div class="hph-financial-item">';
                echo '<span class="hph-financial-label">' . esc_html__('Annual Insurance', 'happy-place') . '</span>';
                echo '<span class="hph-financial-value">' . \hph_format_price($taxes['insurance_annual']) . '</span>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        // Monthly payment breakdown
        if (!empty($financial['buyer'])) {
            $buyer = $financial['buyer'];
            echo '<div class="hph-financial-section">';
            echo '<h4 class="hph-financial-subtitle">' . esc_html__('Monthly Payment Estimate', 'happy-place') . '</h4>';
            echo '<div class="hph-payment-breakdown">';
            
            if (!empty($buyer['monthly_payment_pi'])) {
                echo '<div class="hph-payment-item">';
                echo '<span class="hph-payment-label">' . esc_html__('Principal & Interest', 'happy-place') . '</span>';
                echo '<span class="hph-payment-value">' . \hph_format_price($buyer['monthly_payment_pi']) . '</span>';
                echo '</div>';
            }
            
            if (!empty($buyer['monthly_payment_total'])) {
                echo '<div class="hph-payment-item hph-payment-item--total">';
                echo '<span class="hph-payment-label">' . esc_html__('Total Monthly Payment', 'happy-place') . '</span>';
                echo '<span class="hph-payment-value">' . \hph_format_price($buyer['monthly_payment_total']) . '</span>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render market section
     */
    private function render_market_section($market) {
        if (empty($market)) {
            return;
        }
        
        echo '<div class="hph-market-analysis">';
        
        echo '<div class="hph-market-position">';
        echo '<h4 class="hph-market-subtitle">' . esc_html__('Market Position', 'happy-place') . '</h4>';
        
        if (!empty($market['market_position_text'])) {
            echo '<p class="hph-market-position-text">' . esc_html($market['market_position_text']) . '</p>';
        }
        
        if (!empty($market['comparable_avg']) && $market['comparable_avg'] > 0) {
            echo '<div class="hph-market-comparisons">';
            echo '<div class="hph-comparison-item">';
            echo '<span class="hph-comparison-label">' . esc_html__('Comparable Sales Average', 'happy-place') . '</span>';
            echo '<span class="hph-comparison-value">' . \hph_format_price($market['comparable_avg']) . '</span>';
            echo '</div>';
            
            if ($market['price_vs_comps'] != 0) {
                $direction = $market['price_vs_comps'] > 0 ? 'above' : 'below';
                $percentage = abs($market['price_vs_comps']);
                echo '<div class="hph-comparison-item">';
                echo '<span class="hph-comparison-label">' . esc_html__('vs. Comparables', 'happy-place') . '</span>';
                echo '<span class="hph-comparison-value hph-comparison--' . esc_attr($direction) . '">';
                echo esc_html(number_format($percentage, 1)) . '% ' . esc_html($direction);
                echo '</span>';
                echo '</div>';
            }
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Get active sections based on component props
     */
    private function get_active_sections() {
        $all_sections = $this->get_prop('sections');
        $active_sections = [];
        
        foreach ($all_sections as $section) {
            $show_prop = 'show_' . $section;
            if ($this->get_prop($show_prop)) {
                $active_sections[] = $section;
            }
        }
        
        return $active_sections;
    }
    
    /**
     * Get section title
     */
    private function get_section_title($section) {
        $titles = [
            'description' => __('Description', 'happy-place'),
            'features' => __('Features', 'happy-place'),
            'specifications' => __('Specifications', 'happy-place'),
            'location' => __('Location', 'happy-place'),
            'financial' => __('Financial', 'happy-place'),
            'market' => __('Market Analysis', 'happy-place')
        ];
        
        return $titles[$section] ?? ucwords($section);
    }
}
