<?php
/**
 * Template Part: Filter Sidebar
 * 
 * Displays collapsible filters for property listings
 * 
 * @package HappyPlace
 */

// Get current filter values
$current_filters = [
    'price_min' => isset($_GET['price_min']) ? intval($_GET['price_min']) : '',
    'price_max' => isset($_GET['price_max']) ? intval($_GET['price_max']) : '',
    'bedrooms' => isset($_GET['bedrooms']) ? intval($_GET['bedrooms']) : '',
    'bathrooms' => isset($_GET['bathrooms']) ? intval($_GET['bathrooms']) : '',
    'sq_ft_min' => isset($_GET['sq_ft_min']) ? intval($_GET['sq_ft_min']) : '',
    'sq_ft_max' => isset($_GET['sq_ft_max']) ? intval($_GET['sq_ft_max']) : '',
    'lot_size_min' => isset($_GET['lot_size_min']) ? intval($_GET['lot_size_min']) : '',
    'lot_size_max' => isset($_GET['lot_size_max']) ? intval($_GET['lot_size_max']) : '',
    'features' => isset($_GET['features']) && is_array($_GET['features']) ? $_GET['features'] : [],
    'year_built_min' => isset($_GET['year_built_min']) ? intval($_GET['year_built_min']) : '',
    'year_built_max' => isset($_GET['year_built_max']) ? intval($_GET['year_built_max']) : '',
    'days_on_market' => isset($_GET['days_on_market']) ? intval($_GET['days_on_market']) : '',
];

// Features grouped by category
$feature_groups = [
    'interior' => [
        'hardwood_floors' => __('Hardwood Floors', 'happy-place'),
        'fireplace' => __('Fireplace', 'happy-place'),
        'basement' => __('Basement', 'happy-place'),
        'walk_in_closet' => __('Walk-in Closet', 'happy-place'),
        'updated_kitchen' => __('Updated Kitchen', 'happy-place'),
    ],
    'exterior' => [
        'pool' => __('Pool', 'happy-place'),
        'garage' => __('Garage', 'happy-place'),
        'waterfront' => __('Waterfront', 'happy-place'),
        'deck_patio' => __('Deck/Patio', 'happy-place'),
        'new_construction' => __('New Construction', 'happy-place'),
    ],
    'utility' => [
        'central_air' => __('Central Air', 'happy-place'),
        'solar_panels' => __('Solar Panels', 'happy-place'),
        'generator' => __('Generator', 'happy-place'),
        'security_system' => __('Security System', 'happy-place'),
        'smart_home' => __('Smart Home', 'happy-place'),
    ],
];
?>

<div class="hph-filter-sidebar">
    <form id="hph-filters-form" class="hph-filters-form" method="get">
        <!-- Preserve existing view mode and sort parameters -->
        <?php if (isset($_GET['view_mode'])): ?>
            <input type="hidden" name="view_mode" value="<?php echo esc_attr($_GET['view_mode']); ?>">
        <?php endif; ?>
        
        <?php if (isset($_GET['sort'])): ?>
            <input type="hidden" name="sort" value="<?php echo esc_attr($_GET['sort']); ?>">
        <?php endif; ?>
        
        <!-- Always Open Filters -->
        <div class="hph-filter-section hph-filter-section--open">
            <h3 class="hph-filter-title"><?php esc_html_e('Price Range', 'happy-place'); ?></h3>
            <div class="hph-filter-content">
                <div class="hph-price-range">
                    <div class="hph-form-group">
                        <label for="price_min" class="hph-form-label"><?php esc_html_e('Min Price', 'happy-place'); ?></label>
                        <div class="hph-price-input">
                            <span class="hph-currency-symbol">$</span>
                            <input type="text" id="price_min" name="price_min" class="hph-form-control" 
                                   placeholder="<?php esc_attr_e('No Min', 'happy-place'); ?>"
                                   value="<?php echo esc_attr($current_filters['price_min']); ?>">
                        </div>
                    </div>
                    <span class="hph-range-separator">-</span>
                    <div class="hph-form-group">
                        <label for="price_max" class="hph-form-label"><?php esc_html_e('Max Price', 'happy-place'); ?></label>
                        <div class="hph-price-input">
                            <span class="hph-currency-symbol">$</span>
                            <input type="text" id="price_max" name="price_max" class="hph-form-control" 
                                   placeholder="<?php esc_attr_e('No Max', 'happy-place'); ?>"
                                   value="<?php echo esc_attr($current_filters['price_max']); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hph-filter-section hph-filter-section--open">
            <h3 class="hph-filter-title"><?php esc_html_e('Beds & Baths', 'happy-place'); ?></h3>
            <div class="hph-filter-content">
                <div class="hph-form-row">
                    <div class="hph-form-group">
                        <label for="bedrooms" class="hph-form-label"><?php esc_html_e('Bedrooms', 'happy-place'); ?></label>
                        <select id="bedrooms" name="bedrooms" class="hph-form-select">
                            <option value=""><?php esc_html_e('Any', 'happy-place'); ?></option>
                            <option value="1" <?php selected($current_filters['bedrooms'], 1); ?>>1+</option>
                            <option value="2" <?php selected($current_filters['bedrooms'], 2); ?>>2+</option>
                            <option value="3" <?php selected($current_filters['bedrooms'], 3); ?>>3+</option>
                            <option value="4" <?php selected($current_filters['bedrooms'], 4); ?>>4+</option>
                            <option value="5" <?php selected($current_filters['bedrooms'], 5); ?>>5+</option>
                        </select>
                    </div>
                    <div class="hph-form-group">
                        <label for="bathrooms" class="hph-form-label"><?php esc_html_e('Bathrooms', 'happy-place'); ?></label>
                        <select id="bathrooms" name="bathrooms" class="hph-form-select">
                            <option value=""><?php esc_html_e('Any', 'happy-place'); ?></option>
                            <option value="1" <?php selected($current_filters['bathrooms'], 1); ?>>1+</option>
                            <option value="2" <?php selected($current_filters['bathrooms'], 2); ?>>2+</option>
                            <option value="3" <?php selected($current_filters['bathrooms'], 3); ?>>3+</option>
                            <option value="4" <?php selected($current_filters['bathrooms'], 4); ?>>4+</option>
                            <option value="5" <?php selected($current_filters['bathrooms'], 5); ?>>5+</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hph-filter-section hph-filter-section--open">
            <h3 class="hph-filter-title"><?php esc_html_e('Square Footage', 'happy-place'); ?></h3>
            <div class="hph-filter-content">
                <div class="hph-range-inputs">
                    <div class="hph-form-group">
                        <label for="sq_ft_min" class="hph-form-label"><?php esc_html_e('Min Sq Ft', 'happy-place'); ?></label>
                        <input type="text" id="sq_ft_min" name="sq_ft_min" class="hph-form-control" 
                               placeholder="<?php esc_attr_e('No Min', 'happy-place'); ?>"
                               value="<?php echo esc_attr($current_filters['sq_ft_min']); ?>">
                    </div>
                    <span class="hph-range-separator">-</span>
                    <div class="hph-form-group">
                        <label for="sq_ft_max" class="hph-form-label"><?php esc_html_e('Max Sq Ft', 'happy-place'); ?></label>
                        <input type="text" id="sq_ft_max" name="sq_ft_max" class="hph-form-control" 
                               placeholder="<?php esc_attr_e('No Max', 'happy-place'); ?>"
                               value="<?php echo esc_attr($current_filters['sq_ft_max']); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hph-filter-section hph-filter-section--open">
            <h3 class="hph-filter-title"><?php esc_html_e('Lot Size', 'happy-place'); ?></h3>
            <div class="hph-filter-content">
                <div class="hph-range-inputs">
                    <div class="hph-form-group">
                        <label for="lot_size_min" class="hph-form-label"><?php esc_html_e('Min Acres', 'happy-place'); ?></label>
                        <input type="text" id="lot_size_min" name="lot_size_min" class="hph-form-control" 
                               placeholder="<?php esc_attr_e('No Min', 'happy-place'); ?>"
                               value="<?php echo esc_attr($current_filters['lot_size_min']); ?>">
                    </div>
                    <span class="hph-range-separator">-</span>
                    <div class="hph-form-group">
                        <label for="lot_size_max" class="hph-form-label"><?php esc_html_e('Max Acres', 'happy-place'); ?></label>
                        <input type="text" id="lot_size_max" name="lot_size_max" class="hph-form-control" 
                               placeholder="<?php esc_attr_e('No Max', 'happy-place'); ?>"
                               value="<?php echo esc_attr($current_filters['lot_size_max']); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Collapsible Feature Filters -->
        <?php foreach ($feature_groups as $group_key => $features) : ?>
            <div class="hph-filter-section">
                <h3 class="hph-filter-title hph-filter-toggle">
                    <span>
                        <?php 
                        if ($group_key === 'interior') {
                            esc_html_e('Interior Features', 'happy-place');
                        } elseif ($group_key === 'exterior') {
                            esc_html_e('Exterior Features', 'happy-place');
                        } elseif ($group_key === 'utility') {
                            esc_html_e('Utility Features', 'happy-place');
                        }
                        ?>
                    </span>
                    <i class="fas fa-chevron-down"></i>
                </h3>
                <div class="hph-filter-content hph-filter-content--collapsed">
                    <div class="hph-features-checkboxes">
                        <?php foreach ($features as $feature_key => $feature_label) : ?>
                            <div class="hph-feature-checkbox">
                                <input type="checkbox" id="feature_<?php echo esc_attr($feature_key); ?>" 
                                       name="features[]" value="<?php echo esc_attr($feature_key); ?>"
                                       <?php checked(in_array($feature_key, $current_filters['features'])); ?>>
                                <label for="feature_<?php echo esc_attr($feature_key); ?>">
                                    <?php echo esc_html($feature_label); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Additional Collapsible Filters -->
        <div class="hph-filter-section">
            <h3 class="hph-filter-title hph-filter-toggle">
                <span><?php esc_html_e('Year Built', 'happy-place'); ?></span>
                <i class="fas fa-chevron-down"></i>
            </h3>
            <div class="hph-filter-content hph-filter-content--collapsed">
                <div class="hph-range-inputs">
                    <div class="hph-form-group">
                        <label for="year_built_min" class="hph-form-label"><?php esc_html_e('From Year', 'happy-place'); ?></label>
                        <input type="text" id="year_built_min" name="year_built_min" class="hph-form-control" 
                               placeholder="<?php esc_attr_e('Earliest', 'happy-place'); ?>"
                               value="<?php echo esc_attr($current_filters['year_built_min']); ?>">
                    </div>
                    <span class="hph-range-separator">-</span>
                    <div class="hph-form-group">
                        <label for="year_built_max" class="hph-form-label"><?php esc_html_e('To Year', 'happy-place'); ?></label>
                        <input type="text" id="year_built_max" name="year_built_max" class="hph-form-control" 
                               placeholder="<?php esc_attr_e('Latest', 'happy-place'); ?>"
                               value="<?php echo esc_attr($current_filters['year_built_max']); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hph-filter-section">
            <h3 class="hph-filter-title hph-filter-toggle">
                <span><?php esc_html_e('Days on Market', 'happy-place'); ?></span>
                <i class="fas fa-chevron-down"></i>
            </h3>
            <div class="hph-filter-content hph-filter-content--collapsed">
                <div class="hph-form-group">
                    <select id="days_on_market" name="days_on_market" class="hph-form-select">
                        <option value=""><?php esc_html_e('Any', 'happy-place'); ?></option>
                        <option value="1" <?php selected($current_filters['days_on_market'], 1); ?>>
                            <?php esc_html_e('1 day', 'happy-place'); ?>
                        </option>
                        <option value="7" <?php selected($current_filters['days_on_market'], 7); ?>>
                            <?php esc_html_e('7 days', 'happy-place'); ?>
                        </option>
                        <option value="14" <?php selected($current_filters['days_on_market'], 14); ?>>
                            <?php esc_html_e('14 days', 'happy-place'); ?>
                        </option>
                        <option value="30" <?php selected($current_filters['days_on_market'], 30); ?>>
                            <?php esc_html_e('30 days', 'happy-place'); ?>
                        </option>
                        <option value="90" <?php selected($current_filters['days_on_market'], 90); ?>>
                            <?php esc_html_e('90 days', 'happy-place'); ?>
                        </option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Filter Actions -->
        <div class="hph-filter-actions">
            <button type="submit" class="hph-btn hph-btn-primary">
                <i class="fas fa-search"></i> <?php esc_html_e('Apply Filters', 'happy-place'); ?>
            </button>
            <a href="<?php echo esc_url(get_post_type_archive_link('listing')); ?>" class="hph-btn hph-btn-secondary">
                <i class="fas fa-times"></i> <?php esc_html_e('Clear All', 'happy-place'); ?>
            </a>
        </div>
    </form>
</div>