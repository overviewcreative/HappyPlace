<?php
/**
 * Property Comparison Component
 *
 * Side-by-side property comparison tool with detailed feature analysis,
 * pricing comparison, and visual differences highlighting.
 *
 * @package HappyPlace\Components\Tools
 * @since 2.0.0
 */

namespace HappyPlace\Components\Tools;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Property_Comparison extends Base_Component {
    
    /**
     * Component name identifier
     *
     * @return string
     */
    protected function get_component_name() {
        return 'property-comparison';
    }
    
    /**
     * Default component properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            // Properties to compare
            'property_ids' => [],
            'max_properties' => 3,
            'allow_user_selection' => true,
            
            // Layout
            'layout' => 'side-by-side', // side-by-side, stacked, accordion
            'style' => 'detailed', // detailed, compact, minimal
            'responsive_breakpoint' => 768,
            
            // Display Options
            'show_images' => true,
            'show_basic_info' => true,
            'show_pricing' => true,
            'show_features' => true,
            'show_financial_analysis' => true,
            'show_location_data' => true,
            'show_market_analysis' => false,
            
            // Image Options
            'image_size' => 'medium',
            'enable_image_gallery' => true,
            'gallery_style' => 'slider', // slider, grid, lightbox
            
            // Features to Compare
            'compare_fields' => [
                'basic' => ['price', 'bedrooms', 'bathrooms', 'square_footage', 'lot_size', 'year_built'],
                'features' => ['pool', 'garage', 'fireplace', 'air_conditioning', 'basement', 'deck'],
                'financial' => ['property_tax', 'hoa_fees', 'price_per_sqft', 'estimated_payment'],
                'location' => ['school_district', 'walk_score', 'crime_rating', 'nearby_amenities']
            ],
            
            // Analysis Options
            'enable_pros_cons' => true,
            'enable_scoring' => true,
            'scoring_criteria' => [
                'price_value' => 25,
                'location' => 20,
                'features' => 20,
                'condition' => 15,
                'potential' => 20
            ],
            'enable_recommendations' => true,
            
            // Interactive Features
            'enable_notes' => true,
            'enable_favorites' => true,
            'enable_sharing' => true,
            'enable_print' => true,
            'enable_export' => true,
            
            // Behavior
            'highlight_differences' => true,
            'show_missing_data' => true,
            'auto_calculate_metrics' => true,
            'enable_sorting' => true,
            'persistent_comparison' => true,
            
            // Customization
            'custom_fields' => [],
            'custom_scoring' => [],
            'brand_colors' => [
                'primary' => '#007cba',
                'secondary' => '#28a745',
                'accent' => '#ffc107'
            ]
        ];
    }
    
    /**
     * Validate component properties
     */
    protected function validate_props() {
        $property_ids = $this->get_prop('property_ids');
        
        if (empty($property_ids)) {
            $this->add_validation_error('property_ids cannot be empty');
        }
        
        if (count($property_ids) < 2) {
            $this->add_validation_error('At least 2 properties required for comparison');
        }
        
        if (count($property_ids) > $this->get_prop('max_properties')) {
            $this->add_validation_error('Too many properties for comparison. Maximum allowed: ' . $this->get_prop('max_properties'));
        }
        
        $valid_layouts = ['side-by-side', 'stacked', 'accordion'];
        if (!in_array($this->get_prop('layout'), $valid_layouts)) {
            $this->add_validation_error('Invalid layout. Must be: ' . implode(', ', $valid_layouts));
        }
    }
    
    /**
     * Render the component
     *
     * @return string
     */
    protected function render() {
        $property_ids = $this->get_prop('property_ids');
        $properties_data = $this->get_properties_data($property_ids);
        $comparison_data = $this->prepare_comparison_data($properties_data);
        $classes = $this->build_css_classes();
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes); ?>" 
             data-component="property-comparison"
             data-property-count="<?php echo count($property_ids); ?>"
             data-layout="<?php echo esc_attr($this->get_prop('layout')); ?>">
            
            <!-- Comparison Header -->
            <div class="hph-comparison__header">
                <div class="hph-comparison__title">
                    <h2><?php esc_html_e('Property Comparison', 'happy-place'); ?></h2>
                    <span class="hph-comparison__count">
                        <?php printf(_n('%d property', '%d properties', count($property_ids), 'happy-place'), count($property_ids)); ?>
                    </span>
                </div>
                
                <!-- Controls -->
                <div class="hph-comparison__controls">
                    <?php if ($this->get_prop('allow_user_selection')): ?>
                        <button type="button" 
                                class="hph-btn hph-btn--secondary hph-comparison__add"
                                data-action="add-property">
                            <i class="hph-icon hph-icon--plus" aria-hidden="true"></i>
                            <?php esc_html_e('Add Property', 'happy-place'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($this->get_prop('enable_print')): ?>
                        <button type="button" 
                                class="hph-btn hph-btn--outline hph-comparison__print"
                                data-action="print-comparison">
                            <i class="hph-icon hph-icon--print" aria-hidden="true"></i>
                            <?php esc_html_e('Print', 'happy-place'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($this->get_prop('enable_sharing')): ?>
                        <button type="button" 
                                class="hph-btn hph-btn--outline hph-comparison__share"
                                data-action="share-comparison">
                            <i class="hph-icon hph-icon--share" aria-hidden="true"></i>
                            <?php esc_html_e('Share', 'happy-place'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Comparison Table -->
            <div class="hph-comparison__content">
                
                <!-- Property Headers -->
                <div class="hph-comparison__properties">
                    <?php foreach ($properties_data as $index => $property): ?>
                        <div class="hph-comparison__property" data-property-id="<?php echo esc_attr($property['id']); ?>">
                            <?php $this->render_property_header($property, $index); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Basic Information -->
                <?php if ($this->get_prop('show_basic_info')): ?>
                    <div class="hph-comparison__section hph-comparison__section--basic">
                        <h3 class="hph-comparison__section-title"><?php esc_html_e('Basic Information', 'happy-place'); ?></h3>
                        <?php $this->render_basic_comparison($comparison_data['basic']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Pricing Information -->
                <?php if ($this->get_prop('show_pricing')): ?>
                    <div class="hph-comparison__section hph-comparison__section--pricing">
                        <h3 class="hph-comparison__section-title"><?php esc_html_e('Pricing', 'happy-place'); ?></h3>
                        <?php $this->render_pricing_comparison($comparison_data['pricing']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Features Comparison -->
                <?php if ($this->get_prop('show_features')): ?>
                    <div class="hph-comparison__section hph-comparison__section--features">
                        <h3 class="hph-comparison__section-title"><?php esc_html_e('Features', 'happy-place'); ?></h3>
                        <?php $this->render_features_comparison($comparison_data['features']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Financial Analysis -->
                <?php if ($this->get_prop('show_financial_analysis')): ?>
                    <div class="hph-comparison__section hph-comparison__section--financial">
                        <h3 class="hph-comparison__section-title"><?php esc_html_e('Financial Analysis', 'happy-place'); ?></h3>
                        <?php $this->render_financial_comparison($comparison_data['financial']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Location Data -->
                <?php if ($this->get_prop('show_location_data')): ?>
                    <div class="hph-comparison__section hph-comparison__section--location">
                        <h3 class="hph-comparison__section-title"><?php esc_html_e('Location', 'happy-place'); ?></h3>
                        <?php $this->render_location_comparison($comparison_data['location']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Pros & Cons -->
                <?php if ($this->get_prop('enable_pros_cons')): ?>
                    <div class="hph-comparison__section hph-comparison__section--pros-cons">
                        <h3 class="hph-comparison__section-title"><?php esc_html_e('Pros & Cons', 'happy-place'); ?></h3>
                        <?php $this->render_pros_cons($comparison_data['analysis']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Scoring -->
                <?php if ($this->get_prop('enable_scoring')): ?>
                    <div class="hph-comparison__section hph-comparison__section--scoring">
                        <h3 class="hph-comparison__section-title"><?php esc_html_e('Overall Score', 'happy-place'); ?></h3>
                        <?php $this->render_scoring($comparison_data['scores']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Recommendations -->
                <?php if ($this->get_prop('enable_recommendations')): ?>
                    <div class="hph-comparison__section hph-comparison__section--recommendations">
                        <h3 class="hph-comparison__section-title"><?php esc_html_e('Recommendations', 'happy-place'); ?></h3>
                        <?php $this->render_recommendations($comparison_data['recommendations']); ?>
                    </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Notes Section -->
            <?php if ($this->get_prop('enable_notes')): ?>
                <div class="hph-comparison__notes">
                    <h3 class="hph-comparison__notes-title"><?php esc_html_e('Your Notes', 'happy-place'); ?></h3>
                    <textarea class="hph-comparison__notes-textarea" 
                              placeholder="<?php esc_attr_e('Add your comparison notes here...', 'happy-place'); ?>"
                              data-action="save-notes"></textarea>
                </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Comparison Data for JavaScript -->
        <script type="application/json" id="hph-comparison-data">
            <?php echo json_encode($comparison_data); ?>
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Build CSS classes
     */
    private function build_css_classes() {
        $classes = ['hph-property-comparison'];
        
        $classes[] = 'hph-comparison--' . str_replace('-', '_', $this->get_prop('layout'));
        $classes[] = 'hph-comparison--' . $this->get_prop('style');
        
        if ($this->get_prop('highlight_differences')) {
            $classes[] = 'hph-comparison--highlight-differences';
        }
        
        if ($this->get_prop('show_images')) {
            $classes[] = 'hph-comparison--with-images';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Get properties data
     */
    private function get_properties_data($property_ids) {
        $properties = [];
        
        foreach ($property_ids as $property_id) {
            $property_data = $this->get_single_property_data($property_id);
            if ($property_data) {
                $properties[] = $property_data;
            }
        }
        
        return $properties;
    }
    
    /**
     * Get single property data
     */
    private function get_single_property_data($property_id) {
        $post = get_post($property_id);
        if (!$post || $post->post_type !== 'listing') {
            return null;
        }
        
        return [
            'id' => $property_id,
            'title' => get_the_title($property_id),
            'url' => get_permalink($property_id),
            'image' => $this->get_property_image($property_id),
            'gallery' => $this->get_property_gallery($property_id),
            'basic' => $this->get_basic_data($property_id),
            'pricing' => $this->get_pricing_data($property_id),
            'features' => $this->get_features_data($property_id),
            'financial' => $this->get_financial_data($property_id),
            'location' => $this->get_location_data($property_id)
        ];
    }
    
    /**
     * Get property image
     */
    private function get_property_image($property_id) {
        if (has_post_thumbnail($property_id)) {
            return [
                'url' => get_the_post_thumbnail_url($property_id, $this->get_prop('image_size')),
                'alt' => get_post_meta(get_post_thumbnail_id($property_id), '_wp_attachment_image_alt', true)
            ];
        }
        return null;
    }
    
    /**
     * Get property gallery
     */
    private function get_property_gallery($property_id) {
        $gallery_ids = get_post_meta($property_id, '_listing_gallery', true);
        if (empty($gallery_ids)) {
            return [];
        }
        
        $gallery = [];
        foreach ($gallery_ids as $image_id) {
            $gallery[] = [
                'id' => $image_id,
                'url' => wp_get_attachment_image_url($image_id, $this->get_prop('image_size')),
                'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
            ];
        }
        
        return $gallery;
    }
    
    /**
     * Get basic property data
     */
    private function get_basic_data($property_id) {
        return [
            'price' => get_post_meta($property_id, '_listing_price', true),
            'bedrooms' => get_post_meta($property_id, '_listing_bedrooms', true),
            'bathrooms' => get_post_meta($property_id, '_listing_bathrooms', true),
            'square_footage' => get_post_meta($property_id, '_listing_square_footage', true),
            'lot_size' => get_post_meta($property_id, '_listing_lot_size', true),
            'year_built' => get_post_meta($property_id, '_listing_year_built', true),
            'property_type' => get_post_meta($property_id, '_listing_type', true),
            'status' => get_post_meta($property_id, '_listing_status', true) ?: 'For Sale',
            'address' => get_post_meta($property_id, '_listing_address', true)
        ];
    }
    
    /**
     * Get pricing data
     */
    private function get_pricing_data($property_id) {
        $price = (float) get_post_meta($property_id, '_listing_price', true);
        $sqft = (float) get_post_meta($property_id, '_listing_square_footage', true);
        
        return [
            'list_price' => $price,
            'price_per_sqft' => $sqft > 0 ? round($price / $sqft, 2) : 0,
            'property_tax' => get_post_meta($property_id, '_listing_property_tax', true),
            'hoa_fees' => get_post_meta($property_id, '_listing_hoa_fees', true),
            'estimated_payment' => $this->calculate_estimated_payment($price),
            'price_history' => $this->get_price_history($property_id)
        ];
    }
    
    /**
     * Get features data
     */
    private function get_features_data($property_id) {
        $features = [];
        
        // Get standard features
        $standard_features = ['pool', 'garage', 'fireplace', 'air_conditioning', 'basement', 'deck', 'hardwood_floors', 'updated_kitchen'];
        
        foreach ($standard_features as $feature) {
            $features[$feature] = get_post_meta($property_id, '_listing_' . $feature, true) ? true : false;
        }
        
        // Get custom features
        $custom_features = get_post_meta($property_id, '_listing_custom_features', true);
        if (!empty($custom_features)) {
            $features['custom'] = $custom_features;
        }
        
        return $features;
    }
    
    /**
     * Get financial data
     */
    private function get_financial_data($property_id) {
        $price = (float) get_post_meta($property_id, '_listing_price', true);
        
        return [
            'down_payment_20' => $price * 0.20,
            'closing_costs' => $price * 0.025, // Estimated 2.5%
            'monthly_payment' => $this->calculate_monthly_payment($price),
            'property_taxes' => $this->calculate_annual_property_tax($property_id),
            'insurance' => $this->estimate_insurance($price),
            'total_monthly' => $this->calculate_total_monthly_cost($property_id)
        ];
    }
    
    /**
     * Get location data
     */
    private function get_location_data($property_id) {
        return [
            'school_district' => get_post_meta($property_id, '_listing_school_district', true),
            'walk_score' => get_post_meta($property_id, '_listing_walk_score', true),
            'crime_rating' => get_post_meta($property_id, '_listing_crime_rating', true),
            'nearby_amenities' => $this->get_nearby_amenities($property_id),
            'commute_times' => $this->get_commute_times($property_id),
            'neighborhood_rating' => get_post_meta($property_id, '_listing_neighborhood_rating', true)
        ];
    }
    
    /**
     * Prepare comparison data
     */
    private function prepare_comparison_data($properties_data) {
        $comparison = [
            'basic' => $this->prepare_basic_comparison($properties_data),
            'pricing' => $this->prepare_pricing_comparison($properties_data),
            'features' => $this->prepare_features_comparison($properties_data),
            'financial' => $this->prepare_financial_comparison($properties_data),
            'location' => $this->prepare_location_comparison($properties_data),
            'analysis' => $this->analyze_properties($properties_data),
            'scores' => $this->calculate_scores($properties_data),
            'recommendations' => $this->generate_recommendations($properties_data)
        ];
        
        return $comparison;
    }
    
    /**
     * Prepare basic comparison
     */
    private function prepare_basic_comparison($properties_data) {
        $fields = $this->get_prop('compare_fields')['basic'];
        $comparison = [];
        
        foreach ($fields as $field) {
            $comparison[$field] = [
                'label' => $this->get_field_label($field),
                'values' => [],
                'differences' => false
            ];
            
            $unique_values = [];
            foreach ($properties_data as $property) {
                $value = $property['basic'][$field] ?? '';
                $comparison[$field]['values'][] = $this->format_field_value($field, $value);
                $unique_values[] = $value;
            }
            
            // Check if there are differences
            $comparison[$field]['differences'] = count(array_unique($unique_values)) > 1;
        }
        
        return $comparison;
    }
    
    /**
     * Prepare pricing comparison
     */
    private function prepare_pricing_comparison($properties_data) {
        $comparison = [];
        $pricing_fields = ['list_price', 'price_per_sqft', 'property_tax', 'hoa_fees'];
        
        foreach ($pricing_fields as $field) {
            $comparison[$field] = [
                'label' => $this->get_field_label($field),
                'values' => [],
                'differences' => false
            ];
            
            $unique_values = [];
            foreach ($properties_data as $property) {
                $value = $property['pricing'][$field] ?? 0;
                $comparison[$field]['values'][] = $this->format_currency($value);
                $unique_values[] = $value;
            }
            
            $comparison[$field]['differences'] = count(array_unique($unique_values)) > 1;
        }
        
        return $comparison;
    }
    
    /**
     * Prepare features comparison
     */
    private function prepare_features_comparison($properties_data) {
        $fields = $this->get_prop('compare_fields')['features'];
        $comparison = [];
        
        foreach ($fields as $field) {
            $comparison[$field] = [
                'label' => $this->get_field_label($field),
                'values' => [],
                'differences' => false
            ];
            
            $unique_values = [];
            foreach ($properties_data as $property) {
                $value = $property['features'][$field] ?? false;
                $comparison[$field]['values'][] = $value;
                $unique_values[] = $value;
            }
            
            $comparison[$field]['differences'] = count(array_unique($unique_values)) > 1;
        }
        
        return $comparison;
    }
    
    /**
     * Prepare financial comparison
     */
    private function prepare_financial_comparison($properties_data) {
        $comparison = [];
        $financial_fields = ['down_payment_20', 'monthly_payment', 'total_monthly'];
        
        foreach ($financial_fields as $field) {
            $comparison[$field] = [
                'label' => $this->get_field_label($field),
                'values' => [],
                'differences' => false
            ];
            
            $unique_values = [];
            foreach ($properties_data as $property) {
                $value = $property['financial'][$field] ?? 0;
                $comparison[$field]['values'][] = $this->format_currency($value);
                $unique_values[] = $value;
            }
            
            $comparison[$field]['differences'] = count(array_unique($unique_values)) > 1;
        }
        
        return $comparison;
    }
    
    /**
     * Prepare location comparison
     */
    private function prepare_location_comparison($properties_data) {
        $fields = $this->get_prop('compare_fields')['location'];
        $comparison = [];
        
        foreach ($fields as $field) {
            $comparison[$field] = [
                'label' => $this->get_field_label($field),
                'values' => [],
                'differences' => false
            ];
            
            $unique_values = [];
            foreach ($properties_data as $property) {
                $value = $property['location'][$field] ?? '';
                $comparison[$field]['values'][] = $this->format_field_value($field, $value);
                $unique_values[] = $value;
            }
            
            $comparison[$field]['differences'] = count(array_unique($unique_values)) > 1;
        }
        
        return $comparison;
    }
    
    /**
     * Render property header
     */
    private function render_property_header($property, $index) {
        ?>
        <div class="hph-comparison__property-header">
            <!-- Property Image -->
            <?php if ($this->get_prop('show_images') && $property['image']): ?>
                <div class="hph-comparison__property-image">
                    <img src="<?php echo esc_url($property['image']['url']); ?>" 
                         alt="<?php echo esc_attr($property['image']['alt']); ?>"
                         class="hph-comparison__image">
                    
                    <?php if ($this->get_prop('enable_image_gallery') && !empty($property['gallery'])): ?>
                        <button type="button" 
                                class="hph-comparison__gallery-btn"
                                data-action="open-gallery"
                                data-property-id="<?php echo esc_attr($property['id']); ?>">
                            <i class="hph-icon hph-icon--images" aria-hidden="true"></i>
                            <span><?php printf(__('%d photos', 'happy-place'), count($property['gallery'])); ?></span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Property Info -->
            <div class="hph-comparison__property-info">
                <h3 class="hph-comparison__property-title">
                    <a href="<?php echo esc_url($property['url']); ?>" target="_blank">
                        <?php echo esc_html($property['title']); ?>
                    </a>
                </h3>
                
                <div class="hph-comparison__property-address">
                    <?php echo esc_html($property['basic']['address'] ?? ''); ?>
                </div>
                
                <div class="hph-comparison__property-price">
                    <?php echo esc_html($this->format_currency($property['basic']['price'] ?? 0)); ?>
                </div>
                
                <!-- Actions -->
                <div class="hph-comparison__property-actions">
                    <?php if ($this->get_prop('enable_favorites')): ?>
                        <button type="button" 
                                class="hph-btn hph-btn--outline hph-btn--small"
                                data-action="toggle-favorite"
                                data-property-id="<?php echo esc_attr($property['id']); ?>">
                            <i class="hph-icon hph-icon--heart" aria-hidden="true"></i>
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" 
                            class="hph-btn hph-btn--outline hph-btn--small"
                            data-action="remove-property"
                            data-property-id="<?php echo esc_attr($property['id']); ?>">
                        <i class="hph-icon hph-icon--times" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render basic comparison
     */
    private function render_basic_comparison($comparison_data) {
        ?>
        <div class="hph-comparison__table">
            <?php foreach ($comparison_data as $field => $data): ?>
                <div class="hph-comparison__row <?php echo $data['differences'] && $this->get_prop('highlight_differences') ? 'hph-comparison__row--different' : ''; ?>">
                    <div class="hph-comparison__label">
                        <?php echo esc_html($data['label']); ?>
                    </div>
                    <?php foreach ($data['values'] as $value): ?>
                        <div class="hph-comparison__value">
                            <?php echo esc_html($value); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render pricing comparison
     */
    private function render_pricing_comparison($comparison_data) {
        ?>
        <div class="hph-comparison__table">
            <?php foreach ($comparison_data as $field => $data): ?>
                <div class="hph-comparison__row <?php echo $data['differences'] && $this->get_prop('highlight_differences') ? 'hph-comparison__row--different' : ''; ?>">
                    <div class="hph-comparison__label">
                        <?php echo esc_html($data['label']); ?>
                    </div>
                    <?php foreach ($data['values'] as $value): ?>
                        <div class="hph-comparison__value hph-comparison__value--currency">
                            <?php echo esc_html($value); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render features comparison
     */
    private function render_features_comparison($comparison_data) {
        ?>
        <div class="hph-comparison__table">
            <?php foreach ($comparison_data as $field => $data): ?>
                <div class="hph-comparison__row <?php echo $data['differences'] && $this->get_prop('highlight_differences') ? 'hph-comparison__row--different' : ''; ?>">
                    <div class="hph-comparison__label">
                        <?php echo esc_html($data['label']); ?>
                    </div>
                    <?php foreach ($data['values'] as $value): ?>
                        <div class="hph-comparison__value hph-comparison__value--feature">
                            <?php if ($value): ?>
                                <i class="hph-icon hph-icon--check hph-comparison__check" aria-hidden="true"></i>
                                <span class="hph-sr-only"><?php esc_html_e('Yes', 'happy-place'); ?></span>
                            <?php else: ?>
                                <i class="hph-icon hph-icon--times hph-comparison__cross" aria-hidden="true"></i>
                                <span class="hph-sr-only"><?php esc_html_e('No', 'happy-place'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render financial comparison
     */
    private function render_financial_comparison($comparison_data) {
        ?>
        <div class="hph-comparison__table">
            <?php foreach ($comparison_data as $field => $data): ?>
                <div class="hph-comparison__row <?php echo $data['differences'] && $this->get_prop('highlight_differences') ? 'hph-comparison__row--different' : ''; ?>">
                    <div class="hph-comparison__label">
                        <?php echo esc_html($data['label']); ?>
                    </div>
                    <?php foreach ($data['values'] as $value): ?>
                        <div class="hph-comparison__value hph-comparison__value--currency">
                            <?php echo esc_html($value); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render location comparison
     */
    private function render_location_comparison($comparison_data) {
        ?>
        <div class="hph-comparison__table">
            <?php foreach ($comparison_data as $field => $data): ?>
                <div class="hph-comparison__row <?php echo $data['differences'] && $this->get_prop('highlight_differences') ? 'hph-comparison__row--different' : ''; ?>">
                    <div class="hph-comparison__label">
                        <?php echo esc_html($data['label']); ?>
                    </div>
                    <?php foreach ($data['values'] as $value): ?>
                        <div class="hph-comparison__value">
                            <?php echo esc_html($value); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render pros and cons
     */
    private function render_pros_cons($analysis_data) {
        // TODO: Implement AI-powered pros/cons analysis
        echo '<div class="hph-comparison__pros-cons">';
        echo '<p>' . esc_html__('Pros and cons analysis coming soon...', 'happy-place') . '</p>';
        echo '</div>';
    }
    
    /**
     * Render scoring
     */
    private function render_scoring($scores_data) {
        // TODO: Implement property scoring system
        echo '<div class="hph-comparison__scoring">';
        echo '<p>' . esc_html__('Property scoring system coming soon...', 'happy-place') . '</p>';
        echo '</div>';
    }
    
    /**
     * Render recommendations
     */
    private function render_recommendations($recommendations_data) {
        // TODO: Implement AI-powered recommendations
        echo '<div class="hph-comparison__recommendations">';
        echo '<p>' . esc_html__('AI-powered recommendations coming soon...', 'happy-place') . '</p>';
        echo '</div>';
    }
    
    /**
     * Helper methods for data processing
     */
    
    private function get_field_label($field) {
        $labels = [
            'price' => __('Price', 'happy-place'),
            'bedrooms' => __('Bedrooms', 'happy-place'),
            'bathrooms' => __('Bathrooms', 'happy-place'),
            'square_footage' => __('Square Footage', 'happy-place'),
            'lot_size' => __('Lot Size', 'happy-place'),
            'year_built' => __('Year Built', 'happy-place'),
            'list_price' => __('List Price', 'happy-place'),
            'price_per_sqft' => __('Price per Sq Ft', 'happy-place'),
            'property_tax' => __('Property Tax', 'happy-place'),
            'hoa_fees' => __('HOA Fees', 'happy-place'),
            'down_payment_20' => __('20% Down Payment', 'happy-place'),
            'monthly_payment' => __('Monthly Payment', 'happy-place'),
            'total_monthly' => __('Total Monthly Cost', 'happy-place'),
            'pool' => __('Pool', 'happy-place'),
            'garage' => __('Garage', 'happy-place'),
            'fireplace' => __('Fireplace', 'happy-place'),
            'air_conditioning' => __('Air Conditioning', 'happy-place'),
            'basement' => __('Basement', 'happy-place'),
            'deck' => __('Deck/Patio', 'happy-place'),
            'school_district' => __('School District', 'happy-place'),
            'walk_score' => __('Walk Score', 'happy-place'),
            'crime_rating' => __('Crime Rating', 'happy-place'),
            'nearby_amenities' => __('Nearby Amenities', 'happy-place')
        ];
        
        return $labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }
    
    private function format_field_value($field, $value) {
        switch ($field) {
            case 'square_footage':
            case 'lot_size':
                return $value ? number_format($value) . ' sq ft' : 'N/A';
            case 'bedrooms':
            case 'bathrooms':
                return $value ?: 'N/A';
            case 'year_built':
                return $value ?: 'Unknown';
            default:
                return $value ?: 'N/A';
        }
    }
    
    private function format_currency($value) {
        return $value ? '$' . number_format($value) : 'N/A';
    }
    
    // Placeholder methods for financial calculations
    private function calculate_estimated_payment($price) { return round($price * 0.004); }
    private function get_price_history($property_id) { return []; }
    private function calculate_monthly_payment($price) { return round($price * 0.004); }
    private function calculate_annual_property_tax($property_id) { return 0; }
    private function estimate_insurance($price) { return round($price * 0.003); }
    private function calculate_total_monthly_cost($property_id) { return 0; }
    private function get_nearby_amenities($property_id) { return []; }
    private function get_commute_times($property_id) { return []; }
    private function analyze_properties($properties_data) { return []; }
    private function calculate_scores($properties_data) { return []; }
    private function generate_recommendations($properties_data) { return []; }
}
