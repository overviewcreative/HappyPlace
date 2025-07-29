<?php
/**
 * Search Form Component
 *
 * Property search form component with filters and location search.
 *
 * @package HappyPlace\Components\UI
 * @since 2.0.0
 */

namespace HappyPlace\Components\UI;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Search_Form extends Base_Component {
    
    /**
     * Component name identifier
     *
     * @return string
     */
    protected function get_component_name() {
        return 'search-form';
    }
    
    /**
     * Default component properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            'style' => 'horizontal', // horizontal, vertical, inline, modal
            'size' => 'medium', // small, medium, large
            'fields' => [
                'location',
                'property_type',
                'price_range',
                'bedrooms',
                'bathrooms'
            ],
            'location_field_type' => 'autocomplete', // autocomplete, dropdown, text
            'show_advanced_filters' => true,
            'advanced_fields' => [
                'square_feet',
                'lot_size',
                'year_built',
                'property_features'
            ],
            'submit_button_text' => 'Search Properties',
            'clear_button_text' => 'Clear All',
            'results_url' => '/listings/',
            'enable_ajax' => true,
            'auto_submit' => false,
            'show_results_count' => true,
            'placeholder_texts' => [],
            'default_values' => [],
            'custom_class' => '',
            'enable_save_search' => false,
            'enable_sort_order' => false,
            'compact_mode' => false
        ];
    }
    
    /**
     * Render the component
     *
     * @return string
     */
    protected function render() {
        $form_id = 'hph-search-form-' . uniqid();
        $form_classes = $this->get_form_classes();
        $custom_class = $this->get_prop('custom_class');
        
        ob_start();
        ?>
        <div class="hph-search-form <?php echo esc_attr($form_classes . ' ' . $custom_class); ?>" 
             data-component="search-form"
             data-style="<?php echo esc_attr($this->get_prop('style')); ?>"
             data-ajax="<?php echo $this->get_prop('enable_ajax') ? 'true' : 'false'; ?>">
            
            <form id="<?php echo esc_attr($form_id); ?>" 
                  class="hph-search-form__form" 
                  method="get" 
                  action="<?php echo esc_url($this->get_prop('results_url')); ?>"
                  data-auto-submit="<?php echo $this->get_prop('auto_submit') ? 'true' : 'false'; ?>">
                
                <div class="hph-search-form__main">
                    <?php $this->render_main_fields(); ?>
                </div>
                
                <?php if ($this->get_prop('show_advanced_filters')): ?>
                    <div class="hph-search-form__advanced">
                        <?php $this->render_advanced_toggle(); ?>
                        <?php $this->render_advanced_fields(); ?>
                    </div>
                <?php endif; ?>
                
                <div class="hph-search-form__actions">
                    <?php $this->render_form_actions(); ?>
                </div>
                
                <?php if ($this->get_prop('show_results_count')): ?>
                    <div class="hph-search-form__results-count" style="display: none;">
                        <span class="hph-results-text"></span>
                    </div>
                <?php endif; ?>
                
            </form>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render main search fields
     */
    private function render_main_fields() {
        $fields = $this->get_prop('fields');
        $style = $this->get_prop('style');
        $size = $this->get_prop('size');
        
        foreach ($fields as $field_name) {
            $this->render_search_field($field_name, 'main');
        }
    }
    
    /**
     * Render advanced fields toggle
     */
    private function render_advanced_toggle() {
        echo '<button type="button" class="hph-advanced-toggle" data-toggle="advanced-filters">';
        echo '<span class="hph-toggle-text hph-toggle-text--show">' . esc_html__('More Filters', 'happy-place') . '</span>';
        echo '<span class="hph-toggle-text hph-toggle-text--hide" style="display: none;">' . esc_html__('Fewer Filters', 'happy-place') . '</span>';
        echo '<span class="hph-toggle-icon" aria-hidden="true"></span>';
        echo '</button>';
    }
    
    /**
     * Render advanced fields
     */
    private function render_advanced_fields() {
        $advanced_fields = $this->get_prop('advanced_fields');
        
        echo '<div class="hph-advanced-fields" style="display: none;">';
        
        foreach ($advanced_fields as $field_name) {
            $this->render_search_field($field_name, 'advanced');
        }
        
        echo '</div>';
    }
    
    /**
     * Render individual search field
     */
    private function render_search_field($field_name, $context = 'main') {
        $field_config = $this->get_field_config($field_name);
        $field_id = 'hph-search-' . $field_name . '-' . uniqid();
        $default_value = $this->get_prop('default_values')[$field_name] ?? '';
        $current_value = $_GET[$field_name] ?? $default_value;
        
        $field_classes = ['hph-search-field', 'hph-search-field--' . $field_name];
        if ($context === 'advanced') {
            $field_classes[] = 'hph-search-field--advanced';
        }
        
        echo '<div class="' . esc_attr(implode(' ', $field_classes)) . '">';
        
        switch ($field_config['type']) {
            case 'location':
                $this->render_location_field($field_id, $field_name, $field_config, $current_value);
                break;
            case 'price_range':
                $this->render_price_range_field($field_id, $field_name, $field_config, $current_value);
                break;
            case 'select':
                $this->render_select_field($field_id, $field_name, $field_config, $current_value);
                break;
            case 'number_range':
                $this->render_number_range_field($field_id, $field_name, $field_config, $current_value);
                break;
            case 'checkbox_group':
                $this->render_checkbox_group_field($field_id, $field_name, $field_config, $current_value);
                break;
            default:
                $this->render_text_field($field_id, $field_name, $field_config, $current_value);
                break;
        }
        
        echo '</div>';
    }
    
    /**
     * Render location field
     */
    private function render_location_field($field_id, $field_name, $config, $current_value) {
        $location_type = $this->get_prop('location_field_type');
        $placeholder = $this->get_placeholder($field_name, $config);
        
        echo '<div class="hph-location-field hph-location-field--' . esc_attr($location_type) . '">';
        echo '<label for="' . esc_attr($field_id) . '" class="hph-search-label">' . esc_html($config['label']) . '</label>';
        
        if ($location_type === 'autocomplete') {
            echo '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" ';
            echo 'class="hph-search-input hph-location-autocomplete" ';
            echo 'placeholder="' . esc_attr($placeholder) . '" ';
            echo 'value="' . esc_attr($current_value) . '" ';
            echo 'autocomplete="off" data-autocomplete="location">';
            
            echo '<div class="hph-location-suggestions" style="display: none;"></div>';
            
        } elseif ($location_type === 'dropdown') {
            echo '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" class="hph-search-select">';
            echo '<option value="">' . esc_html($placeholder) . '</option>';
            
            $locations = hph_get_popular_locations();
            foreach ($locations as $location) {
                $selected = $current_value === $location['value'] ? 'selected' : '';
                echo '<option value="' . esc_attr($location['value']) . '" ' . $selected . '>';
                echo esc_html($location['label']);
                echo '</option>';
            }
            echo '</select>';
            
        } else {
            echo '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" ';
            echo 'class="hph-search-input" ';
            echo 'placeholder="' . esc_attr($placeholder) . '" ';
            echo 'value="' . esc_attr($current_value) . '">';
        }
        
        echo '</div>';
    }
    
    /**
     * Render price range field
     */
    private function render_price_range_field($field_id, $field_name, $config, $current_value) {
        $placeholder = $this->get_placeholder($field_name, $config);
        
        echo '<div class="hph-price-range-field">';
        echo '<label for="' . esc_attr($field_id) . '" class="hph-search-label">' . esc_html($config['label']) . '</label>';
        
        // Parse current value if it's a range
        $min_value = '';
        $max_value = '';
        if (!empty($current_value) && strpos($current_value, '-') !== false) {
            list($min_value, $max_value) = explode('-', $current_value, 2);
        }
        
        echo '<div class="hph-price-inputs">';
        echo '<input type="number" name="price_min" class="hph-search-input hph-price-input" ';
        echo 'placeholder="Min Price" value="' . esc_attr($min_value) . '" min="0" step="5000">';
        echo '<span class="hph-price-separator">to</span>';
        echo '<input type="number" name="price_max" class="hph-search-input hph-price-input" ';
        echo 'placeholder="Max Price" value="' . esc_attr($max_value) . '" min="0" step="5000">';
        echo '</div>';
        
        // Quick price buttons
        echo '<div class="hph-price-presets">';
        $price_presets = [
            '0-200000' => 'Under $200K',
            '200000-400000' => '$200K-$400K',
            '400000-600000' => '$400K-$600K',
            '600000-800000' => '$600K-$800K',
            '800000-1000000' => '$800K-$1M',
            '1000000-' => 'Over $1M'
        ];
        
        foreach ($price_presets as $range => $label) {
            $active_class = $current_value === $range ? 'hph-preset--active' : '';
            echo '<button type="button" class="hph-price-preset ' . esc_attr($active_class) . '" data-range="' . esc_attr($range) . '">';
            echo esc_html($label);
            echo '</button>';
        }
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Render select field
     */
    private function render_select_field($field_id, $field_name, $config, $current_value) {
        $placeholder = $this->get_placeholder($field_name, $config);
        
        echo '<div class="hph-select-field">';
        echo '<label for="' . esc_attr($field_id) . '" class="hph-search-label">' . esc_html($config['label']) . '</label>';
        echo '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" class="hph-search-select">';
        echo '<option value="">' . esc_html($placeholder) . '</option>';
        
        foreach ($config['options'] as $value => $label) {
            $selected = (string) $current_value === (string) $value ? 'selected' : '';
            echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '</div>';
    }
    
    /**
     * Render number range field
     */
    private function render_number_range_field($field_id, $field_name, $config, $current_value) {
        echo '<div class="hph-number-range-field">';
        echo '<label for="' . esc_attr($field_id) . '" class="hph-search-label">' . esc_html($config['label']) . '</label>';
        
        $min_value = '';
        $max_value = '';
        if (!empty($current_value) && strpos($current_value, '-') !== false) {
            list($min_value, $max_value) = explode('-', $current_value, 2);
        }
        
        $min_name = $field_name . '_min';
        $max_name = $field_name . '_max';
        
        echo '<div class="hph-range-inputs">';
        echo '<input type="number" name="' . esc_attr($min_name) . '" class="hph-search-input hph-range-input" ';
        echo 'placeholder="Min" value="' . esc_attr($min_value) . '" min="0">';
        echo '<span class="hph-range-separator">-</span>';
        echo '<input type="number" name="' . esc_attr($max_name) . '" class="hph-search-input hph-range-input" ';
        echo 'placeholder="Max" value="' . esc_attr($max_value) . '" min="0">';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Render checkbox group field
     */
    private function render_checkbox_group_field($field_id, $field_name, $config, $current_value) {
        $current_values = is_array($current_value) ? $current_value : explode(',', $current_value);
        $current_values = array_filter($current_values);
        
        echo '<div class="hph-checkbox-group-field">';
        echo '<div class="hph-search-label">' . esc_html($config['label']) . '</div>';
        echo '<div class="hph-checkbox-group">';
        
        foreach ($config['options'] as $value => $label) {
            $checkbox_id = $field_id . '-' . $value;
            $checked = in_array($value, $current_values) ? 'checked' : '';
            
            echo '<label class="hph-checkbox-label" for="' . esc_attr($checkbox_id) . '">';
            echo '<input type="checkbox" id="' . esc_attr($checkbox_id) . '" ';
            echo 'name="' . esc_attr($field_name) . '[]" value="' . esc_attr($value) . '" ' . $checked . ' ';
            echo 'class="hph-checkbox-input">';
            echo '<span class="hph-checkbox-custom"></span>';
            echo '<span class="hph-checkbox-text">' . esc_html($label) . '</span>';
            echo '</label>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render text field
     */
    private function render_text_field($field_id, $field_name, $config, $current_value) {
        $placeholder = $this->get_placeholder($field_name, $config);
        
        echo '<div class="hph-text-field">';
        echo '<label for="' . esc_attr($field_id) . '" class="hph-search-label">' . esc_html($config['label']) . '</label>';
        echo '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" ';
        echo 'class="hph-search-input" placeholder="' . esc_attr($placeholder) . '" ';
        echo 'value="' . esc_attr($current_value) . '">';
        echo '</div>';
    }
    
    /**
     * Render form actions
     */
    private function render_form_actions() {
        $submit_text = $this->get_prop('submit_button_text');
        $clear_text = $this->get_prop('clear_button_text');
        $enable_save = $this->get_prop('enable_save_search');
        $compact_mode = $this->get_prop('compact_mode');
        
        if ($compact_mode) {
            echo '<button type="submit" class="hph-button hph-button--primary hph-search-submit">';
            echo '<span class="hph-search-icon" aria-hidden="true">🔍</span>';
            echo '<span class="hph-sr-only">' . esc_html($submit_text) . '</span>';
            echo '</button>';
        } else {
            echo '<button type="submit" class="hph-button hph-button--primary hph-search-submit">';
            echo esc_html($submit_text);
            echo '</button>';
        }
        
        echo '<button type="button" class="hph-button hph-button--secondary hph-search-clear">';
        echo esc_html($clear_text);
        echo '</button>';
        
        if ($enable_save) {
            echo '<button type="button" class="hph-button hph-button--text hph-save-search">';
            echo '<span class="hph-save-icon" aria-hidden="true">⭐</span>';
            echo '<span class="hph-save-text">' . esc_html__('Save Search', 'happy-place') . '</span>';
            echo '</button>';
        }
    }
    
    /**
     * Get form classes
     */
    private function get_form_classes() {
        $classes = [];
        
        $classes[] = 'hph-search-form--' . $this->get_prop('style');
        $classes[] = 'hph-search-form--' . $this->get_prop('size');
        
        if ($this->get_prop('compact_mode')) {
            $classes[] = 'hph-search-form--compact';
        }
        
        if ($this->get_prop('enable_ajax')) {
            $classes[] = 'hph-search-form--ajax';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Get placeholder text for field
     */
    private function get_placeholder($field_name, $config) {
        $placeholders = $this->get_prop('placeholder_texts');
        
        if (isset($placeholders[$field_name])) {
            return $placeholders[$field_name];
        }
        
        return $config['placeholder'] ?? $config['label'];
    }
    
    /**
     * Get field configuration
     */
    private function get_field_config($field_name) {
        $configs = [
            'location' => [
                'type' => 'location',
                'label' => __('Location', 'happy-place'),
                'placeholder' => __('City, State, ZIP, or Address', 'happy-place')
            ],
            'property_type' => [
                'type' => 'select',
                'label' => __('Property Type', 'happy-place'),
                'placeholder' => __('Any Type', 'happy-place'),
                'options' => hph_get_property_types()
            ],
            'price_range' => [
                'type' => 'price_range',
                'label' => __('Price Range', 'happy-place'),
                'placeholder' => __('Any Price', 'happy-place')
            ],
            'bedrooms' => [
                'type' => 'select',
                'label' => __('Bedrooms', 'happy-place'),
                'placeholder' => __('Any', 'happy-place'),
                'options' => [
                    '1' => '1+',
                    '2' => '2+',
                    '3' => '3+',
                    '4' => '4+',
                    '5' => '5+'
                ]
            ],
            'bathrooms' => [
                'type' => 'select',
                'label' => __('Bathrooms', 'happy-place'),
                'placeholder' => __('Any', 'happy-place'),
                'options' => [
                    '1' => '1+',
                    '1.5' => '1.5+',
                    '2' => '2+',
                    '2.5' => '2.5+',
                    '3' => '3+',
                    '4' => '4+'
                ]
            ],
            'square_feet' => [
                'type' => 'number_range',
                'label' => __('Square Feet', 'happy-place'),
                'placeholder' => __('Any Size', 'happy-place')
            ],
            'lot_size' => [
                'type' => 'number_range',
                'label' => __('Lot Size (acres)', 'happy-place'),
                'placeholder' => __('Any Size', 'happy-place')
            ],
            'year_built' => [
                'type' => 'number_range',
                'label' => __('Year Built', 'happy-place'),
                'placeholder' => __('Any Year', 'happy-place')
            ],
            'property_features' => [
                'type' => 'checkbox_group',
                'label' => __('Features', 'happy-place'),
                'options' => [
                    'pool' => __('Pool', 'happy-place'),
                    'garage' => __('Garage', 'happy-place'),
                    'fireplace' => __('Fireplace', 'happy-place'),
                    'basement' => __('Basement', 'happy-place'),
                    'waterfront' => __('Waterfront', 'happy-place'),
                    'new_construction' => __('New Construction', 'happy-place')
                ]
            ]
        ];
        
        return $configs[$field_name] ?? [
            'type' => 'text',
            'label' => ucwords(str_replace('_', ' ', $field_name)),
            'placeholder' => ''
        ];
    }
}

/**
 * Helper function to get popular locations
 */
if (!function_exists('hph_get_popular_locations')) {
    function hph_get_popular_locations() {
        // This would typically come from your database or cache
        // For now, return some example locations
        return [
            ['value' => 'atlanta-ga', 'label' => 'Atlanta, GA'],
            ['value' => 'austin-tx', 'label' => 'Austin, TX'],
            ['value' => 'charlotte-nc', 'label' => 'Charlotte, NC'],
            ['value' => 'denver-co', 'label' => 'Denver, CO'],
            ['value' => 'miami-fl', 'label' => 'Miami, FL'],
            ['value' => 'nashville-tn', 'label' => 'Nashville, TN'],
            ['value' => 'phoenix-az', 'label' => 'Phoenix, AZ'],
            ['value' => 'raleigh-nc', 'label' => 'Raleigh, NC']
        ];
    }
}
