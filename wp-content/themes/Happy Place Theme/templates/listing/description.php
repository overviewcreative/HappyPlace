<?php
/**
 * Property Description Section
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

namespace HappyPlace\Templates;

if (!defined('ABSPATH')) {
    exit;
}

$data = $args['data'] ?? [];
$listing_id = $args['listing_id'] ?? get_the_ID();

// Get description content
$description = hph_get_listing_field($listing_id, 'description') ?: get_the_content();
$features = $data['features'] ?? [];
$property_type = $data['property_type'] ?: __('Property', 'happy-place');

// Extract neighborhood from address
$address_parts = explode(',', $data['address'] ?? '');
$neighborhood = isset($address_parts[1]) ? trim($address_parts[1]) : '';

// Compile key features for sidebar (max 6)
$key_features = [];
$feature_types = ['interior', 'exterior', 'utility', 'custom'];
foreach ($feature_types as $type) {
    if (!empty($features[$type]) && is_array($features[$type])) {
        $key_features = array_merge($key_features, $features[$type]);
    }
}
$key_features = array_slice(array_unique($key_features), 0, 6);

// Count total features for "view all" button
$total_features = 0;
foreach ($feature_types as $type) {
    if (!empty($features[$type]) && is_array($features[$type])) {
        $total_features += count($features[$type]);
    }
}
?>

<section class="card property-description-card">
    <div class="card-header">
        <h2 class="card-title"><?php esc_html_e('About This Home', 'happy-place'); ?></h2>
        <div class="card-subtitle">
            <?php 
            if ($neighborhood) {
                printf(
                    esc_html__('%s in %s', 'happy-place'),
                    esc_html($property_type),
                    esc_html($neighborhood)
                );
            } else {
                printf(
                    esc_html__('%s in desirable location', 'happy-place'),
                    esc_html($property_type)
                );
            }
            ?>
        </div>
    </div>
    <div class="card-body">
        <div class="property-story">
            
            <div class="description-text">
                <?php if ($description) : ?>
                    <div class="listing-content">
                        <?php echo wp_kses_post(wpautop($description)); ?>
                    </div>
                <?php else : ?>
                    <p class="default-description">
                        <?php esc_html_e('This beautiful property offers modern living in a desirable location. Contact us for more information about this exceptional home.', 'happy-place'); ?>
                    </p>
                <?php endif; ?>
                
                <?php 
                // Hook for additional description content
                do_action('hph_after_listing_description', $listing_id, $data);
                ?>
            </div>
            
            <?php if (!empty($key_features)) : ?>
                <div class="key-features">
                    <h4><?php esc_html_e('Key Features', 'happy-place'); ?></h4>
                    <ul class="feature-list">
                        <?php foreach ($key_features as $feature) : ?>
                            <li class="feature-item">
                                <i class="fas fa-check feature-icon" aria-hidden="true"></i>
                                <span><?php echo esc_html($feature); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <?php if ($total_features > 6) : ?>
                        <button class="btn btn-secondary features-toggle" 
                                type="button"
                                data-action="show-all-features" 
                                data-listing-id="<?php echo esc_attr($listing_id); ?>"
                                style="margin-top: var(--spacing-4); width: 100%;">
                            <i class="fas fa-list" aria-hidden="true"></i>
                            <?php 
                            printf(
                                esc_html__('View All %d Features', 'happy-place'),
                                $total_features
                            ); 
                            ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</section>

<?php if ($total_features > 6) : ?>
    <!-- Hidden detailed features modal/section data -->
    <script type="application/json" id="listing-features-data">
        <?php echo wp_json_encode([
            'listing_id' => $listing_id,
            'features' => [
                'interior' => $features['interior'] ?? [],
                'exterior' => $features['exterior'] ?? [],
                'utility' => $features['utility'] ?? [],
                'custom' => $features['custom'] ?? []
            ],
            'labels' => [
                'interior' => __('Interior Features', 'happy-place'),
                'exterior' => __('Exterior Features', 'happy-place'),
                'utility' => __('Utilities & Systems', 'happy-place'),
                'custom' => __('Additional Features', 'happy-place')
            ]
        ]); ?>
    </script>
<?php endif; ?>