<?php

/**
 * Template Part: Property Filters
 * 
 * This template part displays the property search filters.
 * 
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current filter values
$price_min = isset($_GET['price_min']) ? intval($_GET['price_min']) : '';
$price_max = isset($_GET['price_max']) ? intval($_GET['price_max']) : '';
$bedrooms = isset($_GET['bedrooms']) ? intval($_GET['bedrooms']) : '';
$bathrooms = isset($_GET['bathrooms']) ? intval($_GET['bathrooms']) : '';
$property_type = isset($_GET['property_type']) ? sanitize_text_field($_GET['property_type']) : '';
$location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
?>

<div class="hph-listing-filters">
    <form method="get" class="hph-form" id="property-filters">
        <div class="hph-grid hph-grid-3">
            <div class="hph-form-group">
                <label class="hph-form-label"><?php _e('Location', 'happy-place'); ?></label>
                <input type="text" name="location"
                    class="hph-form-input"
                    placeholder="<?php esc_attr_e('City, Neighborhood, or ZIP', 'happy-place'); ?>"
                    value="<?php echo esc_attr($location); ?>">
            </div>

            <div class="hph-form-group">
                <label class="hph-form-label"><?php _e('Price Range', 'happy-place'); ?></label>
                <div class="hph-form-row">
                    <input type="number" name="price_min"
                        class="hph-form-input"
                        placeholder="<?php esc_attr_e('Min', 'happy-place'); ?>"
                        value="<?php echo esc_attr($price_min); ?>">
                    <input type="number" name="price_max"
                        class="hph-form-input"
                        placeholder="<?php esc_attr_e('Max', 'happy-place'); ?>"
                        value="<?php echo esc_attr($price_max); ?>">
                </div>
            </div>

            <div class="hph-form-group">
                <label class="hph-form-label"><?php _e('Property Type', 'happy-place'); ?></label>
                <select name="property_type" class="hph-form-select">
                    <option value=""><?php _e('All Types', 'happy-place'); ?></option>
                    <?php
                    $types = get_terms([
                        'taxonomy' => 'property_type',
                        'hide_empty' => true
                    ]);
                    foreach ($types as $type) : ?>
                        <option value="<?php echo esc_attr($type->slug); ?>"
                            <?php selected($property_type, $type->slug); ?>>
                            <?php echo esc_html($type->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="hph-form-group">
                <label class="hph-form-label"><?php _e('Bedrooms', 'happy-place'); ?></label>
                <select name="bedrooms" class="hph-form-select">
                    <option value=""><?php _e('Any', 'happy-place'); ?></option>
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <option value="<?php echo $i; ?>" <?php selected($bedrooms, $i); ?>>
                            <?php echo $i . '+'; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="hph-form-group">
                <label class="hph-form-label"><?php _e('Bathrooms', 'happy-place'); ?></label>
                <select name="bathrooms" class="hph-form-select">
                    <option value=""><?php _e('Any', 'happy-place'); ?></option>
                    <?php for ($i = 1; $i <= 4; $i++) : ?>
                        <option value="<?php echo $i; ?>" <?php selected($bathrooms, $i); ?>>
                            <?php echo $i . '+'; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <?php
            // Allow plugins to add custom filters
            do_action('hph_property_filters');
            ?>
        </div>

        <div class="hph-form-actions">
            <button type="submit" class="hph-btn hph-btn-primary">
                <i class="fas fa-search"></i> <?php _e('Search', 'happy-place'); ?>
            </button>
            <a href="<?php echo get_post_type_archive_link('listing'); ?>"
                class="hph-btn hph-btn-secondary">
                <?php _e('Reset Filters', 'happy-place'); ?>
            </a>

            <button type="button"
                class="hph-btn hph-btn-outline js-toggle-advanced-filters">
                <?php _e('Advanced Filters', 'happy-place'); ?>
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>

        <div class="hph-advanced-filters" style="display: none;">
            <!-- Advanced filters will be loaded via AJAX -->
        </div>
    </form>
</div>

<?php
// If filters are active, show filter chips
if ($price_min || $price_max || $bedrooms || $bathrooms || $property_type || $location) {
    get_template_part('templates/template-parts/global/filter-chips');
}
?>