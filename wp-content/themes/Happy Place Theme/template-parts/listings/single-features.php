<?php
/**
 * Single listing features
 */

$listing_id = get_the_ID();
$interior_features = get_post_meta($listing_id, 'interior_features', true);
$exterior_features = get_post_meta($listing_id, 'exterior_features', true);
?>

<div class="details-section">
    <h3 class="section-title">Property Features</h3>
    <div class="features-grid">
        
        <?php if (!empty($interior_features)) : ?>
            <div class="feature-category">
                <h4>Interior Features</h4>
                <ul class="feature-list">
                    <?php foreach ($interior_features as $feature) : ?>
                        <li><?php echo esc_html($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($exterior_features)) : ?>
            <div class="feature-category">
                <h4>Exterior Features</h4>
                <ul class="feature-list">
                    <?php foreach ($exterior_features as $feature) : ?>
                        <li><?php echo esc_html($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (empty($interior_features) && empty($exterior_features)) : ?>
            <div class="feature-category">
                <h4>Interior Features</h4>
                <ul class="feature-list">
                    <li>Hardwood floors</li>
                    <li>Granite countertops</li>
                    <li>Stainless steel appliances</li>
                    <li>Walk-in closets</li>
                </ul>
            </div>
            <div class="feature-category">
                <h4>Exterior Features</h4>
                <ul class="feature-list">
                    <li>Covered patio</li>
                    <li>Landscaped yard</li>
                    <li>2-car garage</li>
                    <li>Sprinkler system</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>
