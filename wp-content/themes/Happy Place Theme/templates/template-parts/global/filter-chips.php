<?php
/**
 * Template part for displaying filter chips
 */

$current_filters = array(
    'price_range' => get_query_var('price_range', 'any'),
    'bedrooms' => get_query_var('bedrooms', 'any'),
    'property_type' => get_query_var('property_type', 'all'),
    'features' => (array) get_query_var('features', array())
);
?>

<div class="hph-filter-section hph-space-y-6">
    <div class="hph-filter-group">
        <h4 class="hph-text-sm hph-font-semibold hph-text-gray-700 hph-mb-3">
            <?php esc_html_e('Price Range', 'happy-place'); ?>
        </h4>
        <div class="hph-flex hph-flex-wrap hph-gap-2">
            <?php
            $price_ranges = array(
                'any' => __('Any Price', 'happy-place'),
                'under-500k' => __('Under $500K', 'happy-place'),
                '500k-800k' => __('$500K - $800K', 'happy-place'),
                '800k-1m' => __('$800K - $1M', 'happy-place'),
                'over-1m' => __('Over $1M', 'happy-place')
            );
            foreach ($price_ranges as $value => $label) :
                $is_active = $current_filters['price_range'] === $value;
            ?>
                <button class="hph-chip <?php echo $is_active ? 'hph-chip--active' : ''; ?>" 
                        data-filter="price_range" 
                        data-value="<?php echo esc_attr($value); ?>"
                        aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>">
                    <?php echo esc_html($label); ?>
                    <?php if ($is_active) : ?>
                        <span class="hph-chip__icon">
                            <i class="fas fa-times"></i>
                        </span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="hph-filter-group">
        <h4 class="hph-text-sm hph-font-semibold hph-text-gray-700 hph-mb-3">
            <?php esc_html_e('Bedrooms', 'happy-place'); ?>
        </h4>
        <div class="hph-flex hph-flex-wrap hph-gap-2">
            <?php
            $bedroom_options = array(
                'any' => __('Any Beds', 'happy-place'),
                '1' => __('1+ Beds', 'happy-place'),
                '2' => __('2+ Beds', 'happy-place'),
                '3' => __('3+ Beds', 'happy-place'),
                '4' => __('4+ Beds', 'happy-place')
            );
            foreach ($bedroom_options as $value => $label) :
                $is_active = $current_filters['bedrooms'] === $value;
            ?>
                <button class="hph-chip <?php echo $is_active ? 'hph-chip--active' : ''; ?>" 
                        data-filter="bedrooms" 
                        data-value="<?php echo esc_attr($value); ?>"
                        aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>">
                    <?php echo esc_html($label); ?>
                    <?php if ($is_active) : ?>
                        <span class="hph-chip__icon">
                            <i class="fas fa-times"></i>
                        </span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="hph-filter-group">
        <h4 class="hph-text-sm hph-font-semibold hph-text-gray-700 hph-mb-3">
            <?php esc_html_e('Property Type', 'happy-place'); ?>
        </h4>
        <div class="hph-flex hph-flex-wrap hph-gap-2">
            <?php
            $property_types = get_terms(array(
                'taxonomy' => 'property_type',
                'hide_empty' => false,
            ));

            // Add "All Types" option
            $all_types = new stdClass();
            $all_types->slug = 'all';
            $all_types->name = __('All Types', 'happy-place');
            array_unshift($property_types, $all_types);

            foreach ($property_types as $type) :
                $is_active = $current_filters['property_type'] === $type->slug;
            ?>
                <button class="hph-chip <?php echo $is_active ? 'hph-chip--active' : ''; ?>" 
                        data-filter="property_type" 
                        data-value="<?php echo esc_attr($type->slug); ?>"
                        aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>">
                    <?php echo esc_html($type->name); ?>
                    <?php if ($is_active) : ?>
                        <span class="hph-chip__icon">
                            <i class="fas fa-times"></i>
                        </span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($current_filters['features'])) : ?>
        <div class="hph-filter-group">
            <h4 class="hph-text-sm hph-font-semibold hph-text-gray-700 hph-mb-3">
                <?php esc_html_e('Features', 'happy-place'); ?>
            </h4>
            <div class="hph-flex hph-flex-wrap hph-gap-2">
                <?php foreach ($current_filters['features'] as $feature) : ?>
                    <button class="hph-chip hph-chip--active" 
                            data-filter="features" 
                            data-value="<?php echo esc_attr($feature); ?>"
                            aria-pressed="true">
                        <?php echo esc_html($feature); ?>
                        <span class="hph-chip__icon">
                            <i class="fas fa-times"></i>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (array_filter($current_filters)) : ?>
        <div class="hph-filter-actions hph-mt-4">
            <button class="hph-btn hph-btn--text hph-btn--sm" data-action="clear-all">
                <i class="fas fa-times-circle hph-mr-1"></i>
                <?php esc_html_e('Clear All Filters', 'happy-place'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>
