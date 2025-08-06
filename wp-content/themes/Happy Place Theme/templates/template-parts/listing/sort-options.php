<?php
/**
 * Template part for displaying sort options
 */

$sort = get_query_var('sort', 'newest');
$total_posts = $wp_query->found_posts;
?>

<div class="hph-results-header hph-flex hph-items-center hph-justify-between hph-p-4 hph-bg-gray-50 hph-border-b hph-border-gray-200">
    <div class="hph-results-count hph-text-gray-600">
        <span id="total-properties" class="hph-font-semibold hph-text-gray-900">
            <?php echo number_format($total_posts); ?>
        </span> 
        <span class="hph-text-sm">
            <?php echo _n('Property Found', 'Properties Found', $total_posts, 'happy-place'); ?>
        </span>
    </div>
    
    <div class="hph-sort-wrapper hph-flex hph-items-center hph-space-x-2">
        <label for="sort-listings" class="hph-text-sm hph-text-gray-600">
            <?php esc_html_e('Sort by:', 'happy-place'); ?>
        </label>
        <div class="hph-input-group">
            <select id="sort-listings" class="hph-form-select hph-form-select--sm">
                <option value="newest" <?php selected($sort, 'newest'); ?>>
                    <?php esc_html_e('Newest Listings', 'happy-place'); ?>
                </option>
                <option value="price-low" <?php selected($sort, 'price-low'); ?>>
                    <?php esc_html_e('Price: Low to High', 'happy-place'); ?>
                </option>
                <option value="price-high" <?php selected($sort, 'price-high'); ?>>
                    <?php esc_html_e('Price: High to Low', 'happy-place'); ?>
                </option>
                <option value="largest" <?php selected($sort, 'largest'); ?>>
                    <?php esc_html_e('Largest', 'happy-place'); ?>
                </option>
            </select>
            <span class="hph-input-group-icon">
                <i class="fas fa-sort"></i>
            </span>
        </div>
    </div>
</div>
